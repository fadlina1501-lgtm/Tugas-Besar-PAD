@extends('layouts.app')

@section('title', 'Work Order Teknisi')
@section('page-title', 'Work Order')

@section('content')
<div class="page-intro">
    <div>
        <h2>Daftar penugasan saya</h2>
        <p class="muted">Terima work order, mulai perbaikan, catat progres dan biaya, serta selesaikan pekerjaan.</p>
    </div>
    <span class="panel-tag">TECHNICIAN WORKSPACE</span>
</div>

@forelse($workOrders as $wo)
    <div class="panel work-card">
        <div class="work-card-head">
            <div>
                <div class="wo-kicker">{{ $wo->work_order_number }} <span>· {{ $wo->maintenanceType->name }}</span></div>
                <h3>{{ $wo->ticket->damageReport->asset->name }}</h3>
                <p class="muted">
                    {{ $wo->ticket->damageReport->asset->asset_code }}
                    · {{ $wo->ticket->damageReport->asset->location->label }}
                </p>
            </div>
            <span class="badge {{ $wo->status === 'Completed' ? 'badge-green' : ($wo->status === 'In Progress' ? 'badge-amber' : 'badge-blue') }}">
                {{ $wo->status }}
            </span>
        </div>

        <div class="work-info-grid">
            <div><small>Ticket</small><strong>{{ $wo->ticket->ticket_number }}</strong></div>
            <div><small>Pelapor</small><strong>{{ $wo->ticket->damageReport->reporter->name }}</strong></div>
            <div><small>Mulai</small><strong>{{ $wo->started_at?->format('d M Y, H:i') ?? 'Belum dimulai' }}</strong></div>
            <div><small>Selesai</small><strong>{{ $wo->completed_at?->format('d M Y, H:i') ?? '—' }}</strong></div>
        </div>

        <div class="report-description">
            <strong>Deskripsi kerusakan:</strong><br>
            {{ $wo->ticket->damageReport->description }}
        </div>

        @if($wo->notes)
            <p class="work-note">{{ $wo->notes }}</p>
        @endif

        @if($wo->status === 'Awaiting Acceptance')
            <form method="POST" action="{{ route('work-orders.accept', $wo) }}" class="work-actions">
                @csrf
                <p class="muted small-text">Sarpras telah menetapkan work order ini kepada kamu. Terima penugasan sebelum memulai perbaikan.</p>
                <button class="btn btn-primary btn-sm" type="submit">Terima Work Order</button>
            </form>
        @elseif(in_array($wo->status, ['Assigned', 'In Progress'], true))
            <div class="work-actions">
                <form method="POST" action="{{ route('work-orders.status', $wo) }}" class="status-form">
                    @csrf
                    <div class="field">
                        <label>Aktivitas perbaikan</label>
                        <select name="status" required>
                            @if($wo->status === 'Assigned')
                                <option value="In Progress">Mulai perbaikan</option>
                            @else
                                <option value="In Progress">Simpan progres perbaikan</option>
                                <option value="Completed">Pekerjaan selesai</option>
                            @endif
                        </select>
                    </div>
                    <div class="field">
                        <label>Catatan progres</label>
                        <textarea name="notes" rows="2" placeholder="Catat progres atau hasil perbaikan">{{ old('notes', $wo->notes) }}</textarea>
                    </div>
                    <div class="field">
                        <label>Biaya tenaga kerja (Rp)</label>
                        <input name="labor_cost" type="number" min="0" step="0.01" value="{{ old('labor_cost', $wo->labor_cost) }}" required>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">Simpan progres</button>
                </form>

                @if($wo->status === 'In Progress')
                    @if($parts->isNotEmpty())
                        <form method="POST" action="{{ route('work-orders.parts', $wo) }}" class="parts-form">
                            @csrf
                            <div class="field">
                                <label>Kebutuhan / pemakaian suku cadang</label>
                                <select name="spare_part_id" required>
                                    @foreach($parts as $part)
                                        <option value="{{ $part->id }}">
                                            {{ $part->name }} · stok {{ $part->stock }} · Rp {{ number_format((float) $part->unit_price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field qty-field">
                                <label>Jumlah</label>
                                <input type="number" name="quantity" min="1" value="1" required>
                            </div>
                            <button class="btn btn-outline btn-sm" type="submit">Catat suku cadang</button>
                        </form>
                    @else
                        <p class="muted small-text">Belum ada data suku cadang yang tersedia untuk dicatat.</p>
                    @endif
                @endif
            </div>
        @endif

        @if($wo->spareParts->isNotEmpty())
            <div class="parts-used">
                <strong>Catatan suku cadang</strong>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Nama</th><th>Jumlah</th><th>Harga/unit</th><th>Subtotal</th></tr></thead>
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

        @if($wo->status === 'Completed')
            <div class="work-cost-row">
                <div><small>Biaya tenaga kerja</small><strong>Rp {{ number_format((float) $wo->labor_cost, 0, ',', '.') }}</strong></div>
                <div><small>Biaya suku cadang</small><strong>Rp {{ number_format($wo->spare_part_cost, 0, ',', '.') }}</strong></div>
                <div><small>Total biaya perbaikan</small><strong>Rp {{ number_format($wo->total_cost, 0, ',', '.') }}</strong></div>
                @if($wo->history)
                    <div><small>Downtime</small><strong>{{ $wo->history->downtime_minutes }} menit</strong></div>
                @endif
            </div>
            @if($wo->history)
                <div class="history-strip">
                    <span>✓ Histori maintenance tersimpan</span>
                    <span class="badge {{ $wo->history->sla_breach ? 'badge-red' : 'badge-green' }}">{{ $wo->history->sla_breach ? 'SLA Breach' : 'SLA Met' }}</span>
                    <span class="muted">{{ $wo->history->service_date->format('d M Y, H:i') }}</span>
                </div>
            @endif
        @endif
    </div>
@empty
    <div class="panel empty-state">
        <div class="empty-icon">⚒</div>
        <h3>Belum ada work order</h3>
        <p class="muted">Work order yang ditetapkan Sarpras kepada kamu akan muncul di sini.</p>
    </div>
@endforelse

<div class="pagination-wrap">{{ $workOrders->links() }}</div>
@endsection
