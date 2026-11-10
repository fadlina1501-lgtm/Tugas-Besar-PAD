<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with(['category', 'location'])
            ->orderBy('asset_code')
            ->paginate(12);

        return view('assets.index', compact('assets'));
    }

    public function create()
    {
        $categories = AssetCategory::orderBy('name')->get();

        return view('assets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Asset::create($data);

        return redirect()
            ->route('assets.index')
            ->with('success', 'Aset berhasil ditambahkan.');
    }

    public function edit(Asset $asset)
    {
        $categories = AssetCategory::orderBy('name')->get();

        return view('assets.edit', compact('asset', 'categories'));
    }

    public function update(Request $request, Asset $asset)
    {
        $asset->update($this->validated($request, $asset));

        return redirect()
            ->route('assets.index')
            ->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset)
    {
        if ($asset->damageReports()->exists()) {
            return back()->with(
                'error',
                'Aset sudah memiliki riwayat laporan dan tidak dapat dihapus.'
            );
        }

        $asset->delete();

        return back()->with('success', 'Aset berhasil dihapus.');
    }

    private function validated(
        Request $request,
        ?Asset $asset = null
    ): array {
        $data = $request->validate([
            'asset_code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('assets', 'asset_code')->ignore($asset?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'condition' => [
                'required',
                Rule::in(['Baik', 'Perlu Perbaikan', 'Rusak']),
            ],
            'category_id' => [
                'required',
                'exists:asset_categories,id',
            ],
            'floor_code' => [
                'required',
                Rule::in(['B1', 'B2', 'B3']),
            ],
            'room' => ['required', 'string', 'max:40'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
        ]);

        // Konversi kode lantai ke angka yang tersimpan di database:
        // B1 = 1, B2 = 2, B3 = 3.
        $floor = (int) substr($data['floor_code'], 1);
        $room = trim($data['room']);

        // Cari lokasi yang sudah ada atau buat lokasi baru.
        $location = Location::firstOrCreate([
            'building' => 'Gedung B Cacuk',
            'floor' => $floor,
            'room' => $room,
        ]);

        // Kolom tambahan pada form tidak disimpan langsung ke tabel aset.
        unset($data['floor_code'], $data['room']);

        // Aset tetap menggunakan relasi location_id seperti sebelumnya.
        $data['location_id'] = $location->id;

        return $data;
    }
}
