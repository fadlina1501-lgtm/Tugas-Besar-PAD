@extends('layouts.app')

@section('title', 'Dashboard Monitoring')
@section('page-title', 'Dashboard Monitoring')

@section('content')
<div class="welcome-row">
    <div>
        <div class="eyebrow">RINGKASAN OPERASIONAL</div>
        <h2>Halo, {{ explode(' ', $user->name)[0] }} <span class="wave">✦</span></h2>
        <p class="muted">Ringkasan kondisi fasilitas dan aktivitas maintenance terbaru.</p>
    </div>
    <div class="date-chip">Diperbarui {{ $stats['last_updated']->format('d M Y, H:i') }}</div>
</div>

<div class="panel form-panel" style="padding:16px 18px;margin-bottom:18px">
    <div class="panel-heading" style="padding:0 0 13px">
        <div>
            <h3>Filter dashboard</h3>
            <p>Pilih kategori atau lokasi untuk menyaring ringkasan, grafik, dan daftar pekerjaan.</p>
        </div>
    </div>
    <form method="GET" action="{{ route('dashboard') }}">
        <div class="form-grid" style="align-items:end;gap:12px">
            <div class="field">
                <label for="category_id">Kategori aset</label>
                <select id="category_id" name="category_id">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="location_id">Lokasi</label>
                <select id="location_id" name="location_id">
                    <option value="">Semua lokasi</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string) $locationId === (string) $location->id)>{{ $location->label }}</option>
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
        <div class="metric-top"><span>Total aset terdaftar</span><span class="metric-icon blue">▤</span></div>
        <div class="metric-number">{{ number_format($stats['assets']) }}</div>
        <div class="metric-foot"><span class="status-dot blue-dot"></span> Aset sesuai filter</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>{{ $role === 'pengguna' ? 'Laporan perlu tindak lanjut' : 'Perlu perhatian' }}</span><span class="metric-icon amber">!</span></div>
        <div class="metric-number">{{ number_format($stats['needs_attention']) }}</div>
        <div class="metric-foot"><span class="status-dot amber-dot"></span>{{ $role === 'pengguna' ? 'Laporan yang belum selesai' : 'Aset rusak/perlu perbaikan' }}</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Laporan terbuka</span><span class="metric-icon violet">◉</span></div>
        <div class="metric-number">{{ number_format($stats['open_reports']) }}</div>
        <div class="metric-foot"><span class="status-dot violet-dot"></span> Menunggu tindak lanjut</div>
    </article>
    <article class="metric-card">
        <div class="metric-top"><span>Work order aktif</span><span class="metric-icon green">⚒</span></div>
        <div class="metric-number">{{ number_format($stats['active_work_orders']) }}</div>
        <div class="metric-foot"><span class="status-dot green-dot"></span> Ditugaskan / dikerjakan</div>
    </article>
</div>

@if($role !== 'pengguna')
    <div class="metric-grid">
        <article class="metric-card">
            <div class="metric-top"><span>Total downtime</span><span class="metric-icon amber">◷</span></div>
            <div class="metric-number">{{ number_format($stats['total_downtime']) }}</div>
            <div class="metric-foot">Menit dari histori maintenance</div>
        </article>

        @if(in_array($role, ['sarpras', 'manajemen']))
            <article class="metric-card">
                <div class="metric-top"><span>Total biaya maintenance</span><span class="metric-icon blue">Rp</span></div>
                <div class="metric-number" style="font-size:24px">Rp {{ number_format($stats['total_maintenance_cost'], 0, ',', '.') }}</div>
                <div class="metric-foot">Biaya yang sudah tercatat</div>
            </article>
        @endif

        @if($role === 'manajemen')
            <article class="metric-card">
                <div class="metric-top"><span>Total Life-Cycle Cost</span><span class="metric-icon violet">LCC</span></div>
                <div class="metric-number" style="font-size:24px">Rp {{ number_format($stats['total_life_cycle_cost'], 0, ',', '.') }}</div>
                <div class="metric-foot">Harga beli + biaya maintenance</div>
            </article>
        @else
            <article class="metric-card">
                <div class="metric-top"><span>Pelanggaran SLA</span><span class="metric-icon amber">!</span></div>
                <div class="metric-number">{{ number_format($stats['sla_breaches']) }}</div>
                <div class="metric-foot">Tiket melewati batas waktu</div>
            </article>
        @endif
    </div>
@endif

{{-- Pengguna Fasilitas tidak melihat biaya maintenance. Teknisi tidak melihat biaya maupun kondisi aset. --}}
@if(in_array($role, ['pengguna', 'sarpras', 'manajemen']))
    <div class="dashboard-grid {{ $role === 'pengguna' ? 'dashboard-grid-single' : '' }}">
        @if(in_array($role, ['sarpras', 'manajemen']))
            <section class="panel chart-panel">
                <div class="panel-heading">
                    <div><h3>Biaya maintenance</h3><p>Realisasi enam bulan terakhir</p></div>
                    <span class="panel-tag">MAINTENANCE HISTORY</span>
                </div>
                <div class="chart-summary">
                    <strong>Rp {{ number_format($stats['month_cost'], 0, ',', '.') }}</strong>
                    <small>Total biaya pada bulan ini</small>
                </div>
                <div class="chart-wrap"><canvas id="costChart" aria-label="Grafik biaya maintenance enam bulan terakhir"></canvas></div>
            </section>
        @endif

        <section class="panel chart-panel">
            <div class="panel-heading">
                <div><h3>Kondisi aset</h3><p>Komposisi kondisi inventaris terfilter</p></div>
                <span class="panel-tag">ASSET HEALTH</span>
            </div>
            <div class="donut-wrap">
                <canvas id="conditionChart" aria-label="Grafik kondisi aset"></canvas>
                <div class="donut-center"><strong>{{ array_sum($conditions->toArray()) }}</strong><small>Total aset</small></div>
            </div>
            <div class="legend-list">
                @forelse($conditions as $condition => $total)
                    <div><span class="legend-name"><i class="legend-dot dot-{{ \Illuminate\Support\Str::slug($condition) }}"></i>{{ $condition }}</span><strong>{{ $total }}</strong></div>
                @empty
                    <p class="muted">Belum ada data aset.</p>
                @endforelse
            </div>
        </section>
    </div>
@endif

@if($role === 'manajemen')
    <section class="panel">
        <div class="panel-heading">
            <div><h3>Evaluasi kelayakan aset</h3><p>Daftar awal berdasarkan kondisi, laporan tervalidasi, dan perbandingan biaya perawatan dengan harga beli.</p></div>
            <span class="panel-tag">REVIEW ASET</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Aset</th><th>Kondisi</th><th>Laporan tervalidasi</th><th>Harga beli</th><th>Biaya maintenance</th><th>Life-Cycle Cost</th><th>Rekomendasi awal</th></tr></thead>
                <tbody>
                    @forelse($eligibilityAssets as $asset)
                        @php($recommendation = $asset->getRekomendasiKelayakan())
                        <tr>
                            <td><strong>{{ $asset->name }}</strong><small>{{ $asset->asset_code }} · {{ $asset->category->name }} · {{ $asset->location->label }}</small></td>
                            <td><span class="badge {{ $asset->condition === 'Baik' ? 'badge-green' : ($asset->condition === 'Rusak' ? 'badge-red' : 'badge-amber') }}">{{ $asset->condition }}</span></td>
                            <td>{{ $asset->failure_count }}</td>
                            <td>Rp {{ number_format((float) $asset->purchase_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($asset->total_maintenance_cost, 0, ',', '.') }}</td>
                            <td><strong>Rp {{ number_format($asset->life_cycle_cost, 0, ',', '.') }}</strong></td>
                            <td><span class="badge {{ $recommendation === 'Prioritas evaluasi/penggantian' ? 'badge-red' : 'badge-amber' }}">{{ $recommendation }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">Tidak ada aset yang memerlukan evaluasi tambahan berdasarkan data saat ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="muted small-text" style="padding:0 20px 16px;margin:0">Catatan: Life-Cycle Cost dihitung dari harga pembelian + biaya maintenance yang tercatat. Rekomendasi adalah alat bantu awal, bukan keputusan otomatis untuk mengganti aset.</p>
    </section>
@endif

<div class="lower-grid">
    <section class="panel">
        <div class="panel-heading">
            <div><h3>Laporan kerusakan terbaru</h3><p>Pemantauan permintaan fasilitas</p></div>
            <a class="text-link" href="{{ route('reports.index') }}">Lihat semua ↗</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No. laporan</th><th>Aset</th><th>Pelapor</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($latestReports as $report)
                        <tr>
                            <td><strong>{{ $report->report_number }}</strong><small>{{ $report->reported_at?->format('d M Y, H:i') }}</small></td>
                            <td>{{ $report->asset->name }}<small>{{ $report->asset->asset_code }}</small></td>
                            <td>{{ $report->reporter->name }}</td>
                            <td><span class="badge {{ $report->status === 'Verified' ? 'badge-green' : (in_array($report->status, ['Need Revision']) ? 'badge-amber' : 'badge-blue') }}">{{ $report->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-cell">Belum ada laporan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel sla-panel">
        <div class="panel-heading"><div><h3>Perhatian SLA</h3><p>Tiket yang melewati batas waktu</p></div><span class="metric-icon amber">◷</span></div>
        <div class="sla-number">{{ $stats['sla_breaches'] }}<small>tiket melewati SLA</small></div>
        <p class="muted small-text">Periksa tiket yang belum selesai dan prioritaskan tindak lanjut untuk menjaga layanan fasilitas.</p>
        @if(in_array($role, ['sarpras', 'manajemen']))
            <a class="btn btn-outline btn-full" href="{{ route('tickets.index') }}">Buka tiket maintenance →</a>
        @elseif($role === 'teknisi')
            <a class="btn btn-outline btn-full" href="{{ route('work-orders.index') }}">Buka work order →</a>
        @else
            <a class="btn btn-outline btn-full" href="{{ route('reports.index') }}">Lihat laporan saya →</a>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
@if(in_array($role, ['sarpras', 'manajemen']))
const costLabels = @json($chartLabels);
const costValues = @json($chartCosts);
new Chart(document.getElementById('costChart'), {
    type: 'line',
    data: { labels: costLabels, datasets: [{ label: 'Biaya (Rp)', data: costValues, borderColor: '#3977df', backgroundColor: 'rgba(57,119,223,.12)', fill: true, tension: .35, borderWidth: 2.5, pointRadius: 3, pointBackgroundColor: '#3977df' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => ' Rp ' + Number(ctx.raw).toLocaleString('id-ID') } } }, scales: { y: { beginAtZero: true, grid: { color: '#edf1f6' }, ticks: { callback: (value) => value === 0 ? '0' : (value / 1000000).toLocaleString('id-ID') + ' jt' } }, x: { grid: { display: false } } } }
});
@endif
@if(in_array($role, ['pengguna', 'sarpras', 'manajemen']))
const condLabels = @json($conditions->keys()->values());
const condValues = @json($conditions->values()->values());
new Chart(document.getElementById('conditionChart'), {
    type: 'doughnut',
    data: { labels: condLabels, datasets: [{ data: condValues, backgroundColor: ['#21a67a', '#f0ad42', '#dc6262', '#7b8aa4'], borderWidth: 0, hoverOffset: 5 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '74%', plugins: { legend: { display: false } } }
});
@endif
</script>
@endpush
