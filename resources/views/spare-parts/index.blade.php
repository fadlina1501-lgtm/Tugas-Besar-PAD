@extends('layouts.app')

@section('title', 'Suku Cadang')
@section('page-title', 'Suku Cadang')

@section('content')
@php($isTechnician = auth()->user()->role === 'teknisi')
<div class="page-intro">
    <div>
        <h2>Data komponen suku cadang</h2>
        <p class="muted">Teknisi mencatat komponen yang digunakan dan harga aktualnya. Kampus tidak mengelola stok suku cadang.</p>
    </div>
    <span class="panel-tag">SPARE PART RECORDS</span>
</div>

@if($isTechnician)
    <div class="panel form-panel">
        <div class="panel-heading">
            <div><h3>Tambah komponen</h3><p>Daftarkan komponen agar dapat dicatat pada work order.</p></div>
        </div>
        <form method="POST" action="{{ route('spare-parts.store') }}">
            @csrf
            <div class="form-grid">
                <div class="field"><label>SKU / kode *</label><input name="sku" value="{{ old('sku') }}" placeholder="SP-AC-001" required></div>
                <div class="field"><label>Nama komponen *</label><input name="name" value="{{ old('name') }}" placeholder="Filter AC" required></div>
                <div class="field"><label>Harga referensi per unit (Rp) *</label><input name="unit_price" type="number" min="0" max="999999999" step="0.01" value="{{ old('unit_price', 0) }}" required></div>
                <div class="field"><label>Satuan *</label><input name="unit" value="{{ old('unit', 'unit') }}" placeholder="unit, pcs, meter" required></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">＋ Simpan komponen</button></div>
        </form>
    </div>
@endif

<div class="panel">
    <div class="panel-heading">
        <div><h3>Daftar komponen</h3><p>{{ $parts->total() }} komponen terdaftar · bukan persediaan stok kampus</p></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>SKU</th><th>Nama komponen</th><th>Harga referensi/unit</th><th>Satuan</th><th>Pengelolaan</th></tr></thead>
            <tbody>
                @forelse($parts as $part)
                    <tr>
                        <td><strong>{{ $part->sku }}</strong></td>
                        <td>{{ $part->name }}</td>
                        <td>Rp {{ number_format((float) $part->unit_price, 0, ',', '.') }}</td>
                        <td>{{ $part->unit }}</td>
                        <td>
                            @if($isTechnician)
                                <details>
                                    <summary class="text-link">Edit komponen / harga</summary>
                                    <form class="compact-form" method="POST" action="{{ route('spare-parts.update', $part) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="field"><label>SKU</label><input name="sku" value="{{ $part->sku }}" required></div>
                                        <div class="field"><label>Nama komponen</label><input name="name" value="{{ $part->name }}" required></div>
                                        <div class="field"><label>Harga referensi/unit</label><input type="number" min="0" max="999999999" step="0.01" name="unit_price" value="{{ $part->unit_price }}" required></div>
                                        <div class="field"><label>Satuan</label><input name="unit" value="{{ $part->unit }}" required></div>
                                        <button class="btn btn-primary btn-sm" type="submit">Simpan perubahan</button>
                                    </form>
                                </details>
                            @else
                                <span class="muted">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty-cell">Belum ada data komponen suku cadang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $parts->links() }}</div>
</div>
@endsection
