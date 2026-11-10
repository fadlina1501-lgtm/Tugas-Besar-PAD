<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use App\Models\MaintenanceHistory;
use Illuminate\Http\Request;

class LifeCycleCostController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'category_id' => [
                'nullable',
                'integer',
                'exists:asset_categories,id',
            ],
            'location_id' => [
                'nullable',
                'integer',
                'exists:locations,id',
            ],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $categoryId = $filters['category_id'] ?? null;
        $locationId = $filters['location_id'] ?? null;
        $search = trim($filters['q'] ?? '');

        // Query utama mengikuti filter yang dipilih.
        $assetQuery = Asset::query()
            ->when(
                $categoryId,
                fn ($query) => $query->where(
                    'category_id',
                    $categoryId
                )
            )
            ->when(
                $locationId,
                fn ($query) => $query->where(
                    'location_id',
                    $locationId
                )
            )
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where(
                        'asset_code',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'name',
                        'like',
                        "%{$search}%"
                    );
                });
            });

        // Ambil aset beserta kategori, lokasi, dan biaya maintenance.
        $assets = (clone $assetQuery)
            ->with(['category', 'location'])
            ->withSum(
                'maintenanceHistories as maintenance_cost_sum',
                'total_cost'
            )
            ->withSum(
                'maintenanceHistories as maintenance_downtime_sum',
                'downtime_minutes'
            )
            ->orderBy('asset_code')
            ->paginate(12)
            ->withQueryString();

        // Ringkasan dihitung dari seluruh aset sesuai filter,
        // bukan hanya dari aset yang muncul di halaman pertama.
        $matchingAssetIds = (clone $assetQuery)
            ->select('assets.id');

        $totalAssets = (clone $assetQuery)->count();

        $purchaseCost = (float) (clone $assetQuery)
            ->sum('purchase_price');

        $maintenanceCost = (float) MaintenanceHistory::query()
            ->whereIn('asset_id', clone $matchingAssetIds)
            ->sum('total_cost');

        $totalDowntime = (int) MaintenanceHistory::query()
            ->whereIn('asset_id', clone $matchingAssetIds)
            ->sum('downtime_minutes');

        // Rumus mengikuti Asset::calculateLifeCycleCost().
        $totalLifeCycleCost = $purchaseCost + $maintenanceCost;

        $categories = AssetCategory::orderBy('name')->get();

        $locations = Location::orderBy('building')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        return view('life-cycle-cost.index', compact(
            'assets',
            'categories',
            'locations',
            'categoryId',
            'locationId',
            'search',
            'totalAssets',
            'purchaseCost',
            'maintenanceCost',
            'totalDowntime',
            'totalLifeCycleCost'
        ));
    }
}
