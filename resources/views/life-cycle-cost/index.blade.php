@extends('layouts.app')

@section('title', 'Life-Cycle Cost')
@section('page-title', 'Life-Cycle Cost')

@section('content')
<div class="page-intro">
    <div>
        <h2>Analisis Life-Cycle Cost</h2>
        <p class="muted">
            Analisis biaya perolehan dan pemeliharaan aset
            Gedung B Cacuk.
        </p>
    </div>

    <span class="panel-tag">ASSET COST ANALYSIS</span>
</div>

{{-- Ringkasan biaya --}}
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-top">
            Jumlah aset
            <span class="metric-icon blue">A</span>
        </div>
        <div class="metric-number">
            {{ number_format($totalAssets, 0, ',', '.') }}
        </div>
        <div class="metric-foot">
            Sesuai filter yang dipilih
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-top">
            Harga perolehan
            <span class="metric-icon blue">Rp</span>
        </div>
        <div class="metric-number">
            {{ number_format($purchaseCost, 0, ',', '.') }}
        </div>
        <div class="metric-foot">
            Total harga pembelian aset
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-top">
            Biaya maintenance
            <span class="metric-icon amber">M</span>
        </div>
        <div class="metric-number">
            {{ number_format($maintenanceCost, 0, ',', '.') }}
        </div>
        <div class="metric-foot">
            Berdasarkan histori maintenance
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-top">
            Total Life-Cycle Cost
            <span class="metric-icon green">LCC</span>
        </div>
        <div class="metric-number">
            {{ number_format($totalLifeCycleCost, 0, ',', '.') }}
        </div>
        <div class="metric-foot">
            Harga perolehan + biaya maintenance
        </div>
    </div>
</div>

{{-- Downtime ditampilkan terpisah dari biaya LCC --}}
<div class="panel" style="padding: 16px 20px; margin-bottom: 18px;">
    <strong>Total downtime:</strong>

    {{ intdiv($totalDowntime, 60) }} jam
    {{ $totalDowntime % 60 }} menit

    <p class="muted small-text" style="margin: 5px 0 0;">
        Downtime ditampilkan sebagai indikator operasional
        dan belum dikonversi menjadi biaya rupiah dalam rumus LCC ini.
    </p>
</div>

{{-- Filter data --}}
<form
    method="GET"
    action="{{ route('life-cycle-cost.index') }}"
    class="panel"
    style="padding: 20px; margin-bottom: 18px;"
>
    <div class="panel-heading">
        <div>
            <h3>Filter analisis</h3>
            <p>Pilih kategori, lokasi, atau cari nama aset.</p>
        </div>
    </div>

    <div class="form-grid">
        <div class="field">
            <label for="category_id">Kategori aset</label>
            <select id="category_id" name="category_id">
                <option value="">Semua kategori</option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(
                            (string) $categoryId ===
                            (string) $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="location_id">Lokasi</label>
            <select id="location_id" name="location_id">
                <option value="">Semua lokasi</option>

                @foreach ($locations as $location)
                    <option
                        value="{{ $location->id }}"
                        @selected(
                            (string) $locationId ===
                            (string) $location->id
                        )
                    >
                        {{ $location->label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="q">Cari aset</label>
            <input
                id="q"
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="Kode atau nama aset"
            >
        </div>
    </div>

    <div class="form-actions">
        <a
            href="{{ route('life-cycle-cost.index') }}"
            class="btn btn-outline"
        >
            Reset filter
        </a>

        <button type="submit" class="btn btn-primary">
            Terapkan filter
        </button>
    </div>
</form>

{{-- Tabel LCC --}}
<div class="panel">
    <div class="panel-heading">
        <div>
            <h3>Rincian biaya per aset</h3>
            <p>
                {{ $assets->total() }} aset sesuai hasil pencarian.
            </p>
        </div>

        <span class="panel-tag">COST REGISTER</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode aset</th>
                    <th>Nama aset</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                    <th>Harga beli</th>
                    <th>Biaya maintenance</th>
                    <th>Downtime</th>
                    <th>Life-Cycle Cost</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($assets as $asset)
                    @php
                        $minutes = (int) $asset->total_downtime;
                    @endphp

                    <tr>
                        <td>
                            <strong>{{ $asset->asset_code }}</strong>
                        </td>

                        <td>
                            {{ $asset->name }}
                            <small>{{ $asset->condition }}</small>
                        </td>

                        <td>
                            {{ $asset->category?->name ?? '-' }}
                        </td>

                        <td>
                            {{ $asset->location?->label ?? '-' }}
                        </td>

                        <td>
                            Rp {{ number_format(
                                (float) $asset->purchase_price,
                                0, ',', '.'
                            ) }}
                        </td>

                        <td>
                            Rp {{ number_format(
                                $asset->total_maintenance_cost,
                                0, ',', '.'
                            ) }}
                        </td>

                        <td>
                            {{ intdiv($minutes, 60) }} jam
                            {{ $minutes % 60 }} menit
                        </td>

                        <td>
                            <strong>
                                Rp {{ number_format(
                                    $asset->life_cycle_cost,
                                    0, ',', '.'
                                ) }}
                            </strong>
                            <small>
                                Harga beli + biaya maintenance
                            </small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-cell">
                            Belum ada aset yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">
        {{ $assets->links() }}
    </div>
</div>
@endsection
