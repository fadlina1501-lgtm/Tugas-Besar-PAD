@extends('layouts.app')

@section('title', 'Work Order')
@section('page-title', 'Work Order')

@section('content')
@php($role = auth()->user()->role)
<div class="page-intro">
    <div>
        <h2>{{ $role === 'teknisi' ? 'Pekerjaan saya' : 'Monitoring pekerjaan' }}</h2>
        <p class="muted">
            @if($role === 'teknisi')
                Perbarui status pekerjaan dan catat biaya tenaga kerja serta komponen yang benar-benar digunakan.
            @elseif($role === 'sarpras')
                Pantau penugasan work order yang telah diteruskan kepada teknisi.
            @else
                Pantau pelaksanaan pekerjaan dan riwayat biaya pemeliharaan.
            @endif
        </p>
    </div>
    <span class="panel-tag">MAINTENANCE OPERATIONS</span>
</div>

@forelse($workOrders as $wo)
    <div class="panel work-card">
        <div class="work-card-head">
            <div>
                <div class="wo-kicker">{{ $wo->work_order_number }} <span>· {{ $wo->maintenanceType->name }}</span></div>
                <h3>{{ $wo->ticket->damageReport->asset->name }}</h3>
                <p class="muted">{{ $wo->ticket->damageReport->asset->asset_code }} · {{ $wo->ticket->damageReport->asset->location->label }}</p>
            </div>
            <span class="badge {{ $wo->status === 'Completed' ? 'badge-green' : ($wo->status === 'In Progress' ? 'badge-amber' : 'badge-blue') }}">{{ $wo->status }}</span>
        </div>

        <div class="work-info-grid">
            <div><small>Teknisi</small><strong>{{ $wo->technician->name }}</strong></div>
            <div><small>Ticket</small><strong>{{ $wo->ticket->ticket_number }}</strong></div>
            <div><small>Mulai</small><strong>{{ $wo->started_at?->format('d M Y, H:i') ?? 'Belum dimulai' }}</strong></div>
            <div><small>Selesai</small><strong>{{ $wo->completed_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
        </div>

        @if($wo->notes)
            <p class="work-note">{{ $wo->notes }}</p>
        @endif

        {{-- Ringkasan biaya hanya ditampilkan kepada teknisi dan Manajemen. Sarpras tidak menginput biaya. --}}
        @if(in_array($role, ['teknisi', 'manajemen']))
            <div class="work-cost-row">
                <div><small>Biaya tenaga kerja</small><strong>Rp {{ number_format((float) $wo->labor_cost, 0, ',', '.') }}</strong></div>
                <div><small>Biaya komponen</small><strong>Rp {{ number_format($wo->spare_part_cost, 0, ',', '.') }}</strong></div>
                <div><small>Total biaya saat ini</small><strong>Rp {{ number_format($wo->total_cost, 0, ',', '.') }}</strong></div>
                @if($wo->history)<div><small>Downtime</small><strong>{{ $wo->history->downtime_minutes }} menit</strong></div>@endif
            </div>
        @endif

        @if($role === 'teknisi' && $wo->status !== 'Completed')
            <div class="work-actions">
                <div class="work-action-column">
                    <form method="POST" action="{{ route('work-orders.status', $wo) }}" class="status-form">
                        @csrf
                        <div class="field">
                            <label>Perbarui status pekerjaan</label>
                            <select name="status" required>
                                <option value="In Progress" @selected($wo->status === 'In Progress')>In Progress / Mulai atau lanjutkan</option>
                                @if($wo->started_at)<option value="Completed">Completed / Selesaikan</option>@endif
                            </select>
                        </div>
                        <div class="field">
                            <label>Catatan pekerjaan</label>
                            <input name="notes" value="{{ $wo->notes }}" placeholder="Temuan teknisi atau catatan perbaikan">
                        </div>
                        <button class="btn btn-primary btn-sm" type="submit">Simpan status</button>
                    </form>

                    <form method="POST" action="{{ route('work-orders.costs', $wo) }}" class="technician-cost-form">
                        @csrf
                        <div class="field">
                            <label>Biaya tenaga kerja aktual (Rp)</label>
                            <input name="labor_cost" type="number" min="0" max="999999999" step="0.01" value="{{ old('labor_cost', $wo->labor_cost) }}" required>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">Simpan biaya</button>
                    </form>
                </div>

                <form method="POST" action="{{ route('work-orders.parts', $wo) }}" class="parts-form">
                    @csrf
                    @if($parts->isNotEmpty())
                        <div class="field">
                            <label>Komponen yang digunakan</label>
                            <select name="spare_part_id" required onchange="this.form.querySelector('[name=unit_price]').value = this.selectedOptions[0].dataset.price">
                                @foreach($parts as $part)
                                    <option value="{{ $part->id }}" data-price="{{ $part->unit_price }}">{{ $part->name }} · {{ $part->sku }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field qty-field">
                            <label>Jumlah</label>
                            <input type="number" name="quantity" min="1" max="10000" value="1" required>
                        </div>
                        <div class="field price-field">
                            <label>Harga aktual/unit (Rp)</label>
                            <input type="number" name="unit_price" min="0" max="999999999" step="0.01" value="{{ $parts->first()->unit_price }}" required>
                        </div>
                        <button class="btn btn-outline btn-sm" type="submit">Catat pemakaian</button>
                    @else
                        <p class="muted small-text">Belum ada komponen. Tambahkan data komponen melalui menu <a class="text-link" href="{{ route('spare-parts.index') }}">Suku Cadang</a> terlebih dahulu.</p>
                    @endif
                </form>
            </div>
        @endif

        @if(in_array($role, ['teknisi', 'manajemen']) && $wo->spareParts->isNotEmpty())
            <div class="parts-used">
                <strong>Komponen yang tercatat</strong>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nama komponen</th><th>Jumlah</th><th>Harga aktual/unit</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            @foreach($wo->spareParts as $part)
                                <tr>
                                    <td>{{ $part->name }}</td>
                                    <td>{{ $part->pivot->quantity }} {{ $part->unit }}</td>
                                    <td>Rp {{ number_format((float) $part->pivot->unit_price, 0, ',', '.') }}</td>
                                    <td>Rp {{ number_format($part->pivot->quantity * (float) $part->pivot->unit_price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($wo->history)
            <div class="history-strip">
                <span>✓ Histori maintenance tersimpan</span>
                <span class="badge {{ $wo->history->sla_breach ? 'badge-red' : 'badge-green' }}">{{ $wo->history->sla_breach ? 'SLA Breach' : 'SLA Met' }}</span>
                <span class="muted">{{ $wo->history->service_date->format('d M Y, H:i') }}</span>
            </div>
        @endif
    </div>
@empty
    <div class="panel empty-state">
        <div class="empty-icon">⚒</div>
        <h3>Belum ada work order</h3>
        <p class="muted">Work order akan muncul setelah Sarpras membuat penugasan dari tiket yang sudah diverifikasi.</p>
    </div>
@endforelse

<div class="pagination-wrap">{{ $workOrders->links() }}</div>
@endsection
