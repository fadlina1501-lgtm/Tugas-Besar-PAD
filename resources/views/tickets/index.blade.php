@extends('layouts.app')

@section('title', 'Tiket Maintenance')
@section('page-title', 'Tiket Maintenance')

@section('content')
<div class="page-intro">
    <div>
        <h2>Daftar tiket pemeliharaan</h2>
        <p class="muted">Periksa laporan yang telah diverifikasi, buat work order, dan tetapkan teknisi.</p>
    </div>
    <span class="panel-tag">SLA DEFAULT · 24 JAM</span>
</div>

<div class="panel">
    <div class="panel-heading">
        <div>
            <h3>Antrean tiket</h3>
            <p>{{ $tickets->total() }} tiket dalam sistem</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tiket / SLA</th>
                    <th>Laporan &amp; aset</th>
                    <th>Status</th>
                    <th>Work order / penugasan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>
                            <strong>{{ $ticket->ticket_number }}</strong>
                            <small>Dibuat {{ $ticket->opened_at->format('d M Y, H:i') }}</small>
                            <small>Deadline: {{ $ticket->sla_deadline->format('d M Y, H:i') }}</small>
                            @if($ticket->is_sla_breached)
                                <span class="badge badge-red">SLA BREACH</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $ticket->damageReport->asset->name }}</strong>
                            <small>{{ $ticket->damageReport->report_number }} · {{ $ticket->damageReport->asset->asset_code }}</small>
                            <small>Pelapor: {{ $ticket->damageReport->reporter->name }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $ticket->is_sla_breached ? 'badge-red' : ($ticket->status === 'Completed' ? 'badge-green' : 'badge-blue') }}">
                                {{ $ticket->status }}
                            </span>
                        </td>
                        <td>
                            @if($ticket->workOrder)
                                <strong>{{ $ticket->workOrder->work_order_number }}</strong>
                                <small>{{ $ticket->workOrder->maintenanceType->name }} · {{ $ticket->workOrder->technician->name }}</small>
                                <small>Status work order: {{ $ticket->workOrder->status }}</small>
                            @elseif($ticket->status === 'Open')
                                <details class="inline-details">
                                    <summary class="btn btn-primary btn-sm">＋ Buat work order</summary>
                                    <form class="compact-form" method="POST" action="{{ route('tickets.work-orders.store', $ticket) }}">
                                        @csrf
                                        <div class="field">
                                            <label>Jenis maintenance</label>
                                            <select name="maintenance_type_id" required>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Teknisi yang ditugaskan</label>
                                            <select name="technician_id" required>
                                                @foreach($technicians as $tech)
                                                    <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Instruksi pekerjaan</label>
                                            <textarea name="notes" rows="2" placeholder="Instruksi awal untuk teknisi"></textarea>
                                        </div>
                                        <button class="btn btn-primary btn-sm" type="submit">Buat dan tetapkan work order</button>
                                    </form>
                                </details>
                            @else
                                <span class="muted">
                                    @if($ticket->status === 'Pending Verification')
                                        Menunggu verifikasi laporan
                                    @elseif($ticket->status === 'Completed')
                                        Perbaikan selesai
                                    @elseif($ticket->status === 'Awaiting Acceptance')
                                        Menunggu teknisi menerima work order
                                    @elseif($ticket->status === 'Assigned')
                                        Work order telah diterima teknisi
                                    @elseif($ticket->status === 'In Progress')
                                        Perbaikan sedang berlangsung
                                    @else
                                        Belum ada work order
                                    @endif
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-cell">Belum ada tiket maintenance. Tiket dibuat otomatis saat pengguna mengirimkan laporan kerusakan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrap">{{ $tickets->links() }}</div>
</div>
@endsection
