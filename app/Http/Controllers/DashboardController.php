<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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

        // Fungsi filterByKategori() dan filterByLokasi() pada Dashboard.
        $assetQuery = Asset::query();
        $assetQuery = $this->filterByKategori($assetQuery, $categoryId);
        $assetQuery = $this->filterByLokasi($assetQuery, $locationId);

        $matchingAssetIds = (clone $assetQuery)->select('assets.id');
        $historyQuery = MaintenanceHistory::query()
            ->whereIn('asset_id', clone $matchingAssetIds);

        $totalAssets = (clone $assetQuery)->count();
        $purchaseCost = (float) (clone $assetQuery)->sum('purchase_price');
        $maintenanceCost = (float) (clone $historyQuery)->sum('total_cost');
        $totalDowntime = (int) (clone $historyQuery)->sum('downtime_minutes');
        $totalLifeCycleCost = $purchaseCost + $maintenanceCost;

        $slaBreaches = MaintenanceTicket::query()
            ->whereIn('damage_report_id', function ($query) use ($matchingAssetIds) {
                $query->select('id')
                    ->from('damage_reports')
                    ->whereIn('asset_id', clone $matchingAssetIds);
            })
            ->where('status', '!=', 'Completed')
            ->where('sla_deadline', '<', now())
            ->count()
            + (clone $historyQuery)->where('sla_breach', true)->count();

        // tampilkanRingkasan() menyusun nilai yang menjadi atribut Dashboard.
        $stats = $this->update([
            'total_assets' => $totalAssets,
            'total_downtime' => $totalDowntime,
            'total_maintenance_cost' => $maintenanceCost,
            'total_life_cycle_cost' => $totalLifeCycleCost,
            'sla_breaches' => $slaBreaches,
        ]);
        $stats = $this->tampilkanRingkasan($stats);

        $categories = AssetCategory::orderBy('name')->get();
        $locations = Location::orderBy('building')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        // getRekomendasiKelayakan() digunakan Manajemen untuk mengevaluasi aset.
        $eligibilityAssets = (clone $assetQuery)
            ->with(['category', 'location'])
            ->withCount([
                'damageReports as confirmed_failure_count' => fn (Builder $query) => $query->where('status', 'Verified'),
            ])
            ->withCount([
                'damageReports as failure_frequency' => fn (Builder $query) => $query
                    ->where('status', 'Verified')
                    ->where('reported_at', '>=', now()->subYear()),
            ])
            ->withSum('maintenanceHistories as maintenance_cost_sum', 'total_cost')
            ->withSum('maintenanceHistories as maintenance_downtime_sum', 'downtime_minutes')
            ->get()
            ->filter(fn (Asset $asset) => $this->getRekomendasiKelayakan($asset) !== 'Layak dipertahankan')
            ->sortByDesc(function (Asset $asset) {
                $priority = match ($this->getRekomendasiKelayakan($asset)) {
                    'Prioritas evaluasi/penggantian' => 3,
                    'Perlu evaluasi' => 2,
                    default => 1,
                };

                return ($priority * 1000) + (min($asset->failure_count, 10) * 10);
            })
            ->map(function (Asset $asset) {
                $asset->setAttribute('eligibility_recommendation', $this->getRekomendasiKelayakan($asset));
                return $asset;
            })
            ->values();

        return view('dashboard', compact(
            'stats',
            'categories',
            'locations',
            'categoryId',
            'locationId',
            'eligibilityAssets'
        ));
    }

    private function filterByKategori(Builder $query, ?int $categoryId): Builder
    {
        return $query->when(
            $categoryId,
            fn (Builder $builder) => $builder->where('category_id', $categoryId)
        );
    }

    private function filterByLokasi(Builder $query, ?int $locationId): Builder
    {
        return $query->when(
            $locationId,
            fn (Builder $builder) => $builder->where('location_id', $locationId)
        );
    }

    private function update(array $summary): array
    {
        $summary['last_updated'] = now();

        return $summary;
    }

    private function tampilkanRingkasan(array $summary): array
    {
        return $summary;
    }

    private function getRekomendasiKelayakan(Asset $asset): string
    {
        $maintenanceCost = $asset->getTotalMaintenanceCost();
        $purchasePrice = (float) ($asset->purchase_price ?? 0);
        $failureCount = $asset->failure_count;

        if ($asset->condition === 'Rusak' && $failureCount >= 3) {
            return 'Prioritas evaluasi/penggantian';
        }

        if ($asset->condition !== 'Baik' || ($purchasePrice > 0 && $maintenanceCost >= $purchasePrice)) {
            return 'Perlu evaluasi';
        }

        return 'Layak dipertahankan';
    }
}
