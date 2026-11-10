@extends('layouts.app')

@section('title', 'Dashboard Manajemen')
@section('page-title', 'Dashboard Manajemen')

@section('content')
<div class="welcome-row">
    <div>
        <div class="eyebrow">RINGKASAN OPERASIONAL</div>
        <h2>Dashboard Fasilitas <span class="wave">✦</span></h2>
        <p class="muted">Ringkasan pemeliharaan, biaya, downtime, SLA, dan evaluasi kelayakan aset.</p>
    </div>
    <div class="date-chip">Diperbarui {{ $stats['last_updated']->format('d M Y, H:i') }}</div>
</div>

<div class="panel form-panel" style="padding:16px 18px;margin-bottom:18px">
    <div class="panel-heading" style="padding:0 0 13px">
        <div>
            <h3>Filter dashboard</h3>
            <p>Pilih kategori atau lokasi untuk menyaring ringkasan dan evaluasi aset.</p>
        </div>
    </div>
    <form method="GET" action="{{ route('dashboard') }}">
        <div class="form-grid" style="align-items:end;gap:12px">
            <div class="field">
                <label for="category_id">Kategori aset</label>
                <select id="category_id" name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="location_id">Lokasi</label>
                <select id="location_id" name="location_id">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>
                            {{ $location->label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-actions" style="margin-top:13px;padding-top:13px">
            <a class="btn btn-outline" href="{{ route('dashboard') }}">Reset filter</a>
            <button class="btn btn-primary" type="submit">Terapkan filter</button>
        </div>
    </form>
</div>

<div class="metric-grid">
    <article class="metric-card">
        <div class="metric-top"><span>Total aset</span><span class="metric-icon blue">▤</span></div>
        <div class="metric-number">{{ number_format($stats['total_assets']) }}</div>
        <div class="metric-foot">Aset sesuai filter</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Total downtime</span><span class="metric-icon amber">◷</span></div>
        <div class="metric-number">{{ number_format($stats['total_downtime']) }}</div>
        <div class="metric-foot">Menit dari histori maintenance</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Biaya maintenance</span><span class="metric-icon blue">Rp</span></div>
        <div class="metric-number" style="font-size:24px">Rp {{ number_format($stats['total_maintenance_cost'], 0, ',', '.') }}</div>
        <div class="metric-foot">Akumulasi biaya perbaikan</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Life-Cycle Cost</span><span class="metric-icon violet">LCC</span></div>
        <div class="metric-number" style="font-size:24px">Rp {{ number_format($stats['total_life_cycle_cost'], 0, ',', '.') }}</div>
        <div class="metric-foot">Harga perolehan + biaya maintenance</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Pelanggaran SLA</span><span class="metric-icon amber">!</span></div>
        <div class="metric-number">{{ number_format($stats['sla_breaches']) }}</div>
        <div class="metric-foot">Tiket melewati batas SLA</div>
    </article>
</div>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Evaluasi kelayakan aset</h3>
            <p>Rekomendasi awal berdasarkan kondisi, frekuensi kerusakan, dan biaya pemeliharaan.</p>
        </div>
        <span class="panel-tag">ASSET EVALUATION</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Aset</th>
                    <th>Kondisi</th>
                    <th>Frekuensi kerusakan<br><small>12 bulan terakhir</small></th>
                    <th>Harga perolehan</th>
                    <th>Downtime</th>
                    <th>Biaya maintenance</th>
                    <th>Life-Cycle Cost</th>
                    <th>Rekomendasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eligibilityAssets as $asset)
                    @php($recommendation = $asset->eligibility_recommendation)
                    <tr>
                        <td>
                            <strong>{{ $asset->name }}</strong>
                            <small>{{ $asset->asset_code }} · {{ $asset->category->name }} · {{ $asset->location->label }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $asset->condition === 'Baik' ? 'badge-green' : ($asset->condition === 'Rusak' ? 'badge-red' : 'badge-amber') }}">
                                {{ $asset->condition }}
                            </span>
                        </td>
                        <td>{{ $asset->failure_frequency }}</td>
                        <td>Rp {{ number_format((float) $asset->purchase_price, 0, ',', '.') }}</td>
                        <td>{{ number_format($asset->total_downtime, 0, ',', '.') }} menit</td>
                        <td>Rp {{ number_format($asset->total_maintenance_cost, 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format($asset->life_cycle_cost, 0, ',', '.') }}</strong></td>
                        <td>
                            <span class="badge {{ $recommendation === 'Prioritas evaluasi/penggantian' ? 'badge-red' : 'badge-amber' }}">
                                {{ $recommendation }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-cell">Tidak ada aset yang memerlukan evaluasi tambahan berdasarkan data saat ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="muted small-text" style="padding:0 20px 16px;margin:0">
        Life-Cycle Cost dihitung dari harga perolehan ditambah biaya maintenance yang tercatat. Rekomendasi merupakan alat bantu evaluasi bagi Manajemen.
    </p>
</section>
@endsection
