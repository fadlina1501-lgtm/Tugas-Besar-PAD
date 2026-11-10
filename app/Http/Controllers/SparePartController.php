<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SparePartController extends Controller
{
    public function index()
    {
        $parts = SparePart::orderBy('name')->paginate(15);

        return view('spare-parts.index', compact('parts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:60', 'unique:spare_parts,sku'],
            'name' => ['required', 'string', 'max:150'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'unit' => ['required', 'string', 'max:20'],
        ]);

        // Kolom stock dipertahankan untuk kompatibilitas database lama,
        // tetapi tidak digunakan karena kampus tidak menyediakan persediaan suku cadang.
        $data['stock'] = 0;
        SparePart::create($data);

        return back()->with('success', 'Data komponen suku cadang berhasil ditambahkan.');
    }

    public function update(Request $request, SparePart $sparePart)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:60', Rule::unique('spare_parts', 'sku')->ignore($sparePart->id)],
            'name' => ['required', 'string', 'max:150'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'unit' => ['required', 'string', 'max:20'],
        ]);

        $sparePart->update($data);

        return back()->with('success', 'Data komponen dan harga referensi berhasil diperbarui.');
    }
}
