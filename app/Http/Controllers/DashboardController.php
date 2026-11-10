<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\DamageReport;
use App\Models\Location;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $categoryId = $filters['category_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $user = $request->user();
        $role = $user->role;

        $applyAssetFilters = static function (Builder $query) use ($categoryId, $locationId): Builder {
            return $query
                ->when($categoryId, fn (Builder $builder) => $builder->where('category_id', $categoryId))
                ->when($locationId, fn (Builder $builder) => $builder->where('location_id', $locationId));
        };

        $assetQuery = $applyAssetFilters(Asset::query());
        $reportsQuery = DamageReport::query();
        $workOrdersQuery = WorkOrder::query();
        $ticketsQuery = MaintenanceTicket::query();
        $costQuery = MaintenanceHistory::query();

        if ($role === 'pengguna') {
            $reportsQuery->where('reporter_id', $user->id);
            $ticketsQuery->whereHas('damageReport', fn (Builder $query) => $query->where('reporter_id', $user->id));
            $costQuery->whereRaw('1 = 0');
        } elseif ($role === 'teknisi') {
            $reportsQuery->whereHas('ticket.workOrder', fn (Builder $query) => $query->where('technician_id', $user->id));
            $workOrdersQuery->where('technician_id', $user->id);
            $ticketsQuery->whereHas('workOrder', fn (Builder $query) => $query->where('technician_id', $user->id));
            $costQuery->whereHas('workOrder', fn (Builder $query) => $query->where('technician_id', $user->id));
        }

        if ($categoryId || $locationId) {
            $reportsQuery->whereHas('asset', fn (Builder $query) => $applyAssetFilters($query));
            $ticketsQuery->whereHas('damageReport.asset', fn (Builder $query) => $applyAssetFilters($query));
            $workOrdersQuery->whereHas('ticket.damageReport.asset', fn (Builder $query) => $applyAssetFilters($query));
            $costQuery->whereHas('asset', fn (Builder $query) => $applyAssetFilters($query));
        }

        $maintenanceCost = (float) (clone $costQuery)->sum('total_cost');
        $totalDowntime = (int) (clone $costQuery)->sum('downtime_minutes');
        $purchaseCost = $role === 'manajemen' ? (float) (clone $assetQuery)->sum('purchase_price') : 0;

        $stats = [
            'assets' => (clone $assetQuery)->count(),
            'needs_attention' => (clone $assetQuery)->whereIn('condition', ['Rusak', 'Perlu Perbaikan'])->count(),
            'open_reports' => (clone $reportsQuery)->whereIn('status', ['Submitted', 'Revised', 'Need Revision'])->count(),
            'active_work_orders' => (clone $workOrdersQuery)->whereIn('status', ['Assigned', 'In Progress'])->count(),
            'sla_breaches' => (clone $ticketsQuery)->where('status', '!=', 'Completed')->where('sla_deadline', '<', now())->count()
                + (clone $costQuery)->where('sla_breach', true)->count(),
            'month_cost' => (float) (clone $costQuery)->whereMonth('service_date', now()->month)->whereYear('service_date', now()->year)->sum('total_cost'),
            'total_downtime' => $totalDowntime,
            'total_maintenance_cost' => $maintenanceCost,
            'total_life_cycle_cost' => $role === 'manajemen' ? $purchaseCost + $maintenanceCost : 0,
            'last_updated' => now(),
        ];

        if ($role === 'pengguna') {
            $stats['needs_attention'] = $stats['open_reports'];
            $stats['active_work_orders'] = 0;
            $stats['month_cost'] = 0;
            $stats['total_downtime'] = 0;
            $stats['total_maintenance_cost'] = 0;
        }

        $conditions = (clone $assetQuery)
            ->select('condition', DB::raw('COUNT(*) as total'))
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $monthLabels = collect(range(5, 0))->map(fn ($offset) => now()->startOfMonth()->subMonths($offset));
        $chartStart = now()->startOfMonth()->subMonths(5);
        // Group in PHP so the chart works with MySQL and SQLite configurations.
        $costRows = (clone $costQuery)
            ->where('service_date', '>=', $chartStart)
            ->get(['service_date', 'total_cost'])
            ->groupBy(fn (MaintenanceHistory $history) => $history->service_date->format('Y-m'))
            ->map(fn ($histories) => $histories->sum(fn (MaintenanceHistory $history) => (float) $history->total_cost));

        $chartLabels = $monthLabels->map(fn ($date) => $date->translatedFormat('M Y'))->values();
        $chartCosts = $monthLabels->map(fn ($date) => (float) ($costRows[$date->format('Y-m')] ?? 0))->values();

        $latestReports = (clone $reportsQuery)->with(['asset', 'reporter'])->latest('reported_at')->limit(6)->get();
        $latestWorkOrders = (clone $workOrdersQuery)->with(['ticket.damageReport.asset', 'technician', 'maintenanceType'])->latest()->limit(6)->get();
        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('building')->orderBy('floor')->orderBy('room')->get();

        $eligibilityAssets = collect();
        if ($role === 'manajemen') {
            $eligibilityAssets = (clone $assetQuery)
                ->with(['category', 'location'])
                ->withCount(['damageReports as confirmed_failure_count' => fn (Builder $query) => $query->where('status', 'Verified')])
                ->withSum('maintenanceHistories as maintenance_cost_sum', 'total_cost')
                ->get()
                ->filter(fn (Asset $asset) => $asset->getRekomendasiKelayakan() !== 'Layak dipertahankan')
                ->sortByDesc(function (Asset $asset) {
                    $priority = match ($asset->getRekomendasiKelayakan()) {
                        'Prioritas evaluasi/penggantian' => 3,
                        'Perlu evaluasi' => 2,
                        default => 1,
                    };
                    return ($priority * 1000) + (min($asset->failure_count, 10) * 10);
                })
                ->take(6)
                ->values();
        }

        return view('dashboard', compact(
            'user', 'role', 'stats', 'conditions', 'chartLabels', 'chartCosts',
            'latestReports', 'latestWorkOrders', 'categories', 'locations',
            'categoryId', 'locationId', 'eligibilityAssets'
        ));
    }
}
