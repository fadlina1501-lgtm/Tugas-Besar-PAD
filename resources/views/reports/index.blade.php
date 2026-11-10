@extends('layouts.app')
@section('title', 'Laporan Kerusakan')
@section('page-title', 'Laporan Kerusakan')
@section('content')
<div class="page-intro"><div><h2>{{ auth()->user()->role==='pengguna' ? 'Laporan saya' : 'Monitoring laporan' }}</h2><p class="muted">Lacak laporan dan tiket maintenance dari pengiriman, verifikasi Sarpras, hingga penyelesaian perbaikan.</p></div>@if(auth()->user()->role==='pengguna')<a href="{{ route('reports.create') }}" class="btn btn-primary">＋ Laporan baru</a>@endif</div>
@forelse($reports as $report)
<div class="panel report-card"><div class="report-card-head"><div><span class="panel-tag">{{ $report->report_number }}</span><h3>{{ $report->asset->name }} <span class="muted inline-muted">· {{ $report->asset->asset_code }}</span></h3><p class="muted">{{ $report->reported_at->format('d M Y, H:i') }} · {{ $report->asset->location->label }}</p></div><span class="badge {{ $report->status==='Verified'?'badge-green':($report->status==='Need Revision'?'badge-amber':($report->status==='Rejected'?'badge-red':'badge-blue')) }}">{{ $report->status }}</span></div>
<div class="report-description">{{ $report->description }}</div>
<div class="report-meta"><span>Dilaporkan oleh <strong>{{ $report->reporter->name }}</strong></span>@if($report->verifier)<span>Ditinjau oleh <strong>{{ $report->verifier->name }}</strong></span>@endif</div>
@if($report->verification_note)<div class="note-box"><strong>Catatan Sarpras</strong><p>{{ $report->verification_note }}</p></div>@endif
@if(auth()->user()->role==='sarpras' && in_array($report->status,['Submitted','Revised']))
<form method="POST" action="{{ route('reports.verify',$report) }}" class="review-form">@csrf<div class="field"><label>Catatan verifikasi (opsional)</label><input name="verification_note" placeholder="Catatan untuk pelapor atau teknisi"></div><div class="action-row"><button class="btn btn-primary btn-sm" name="decision" value="verified">✓ Verifikasi</button><button class="btn btn-outline btn-sm" name="decision" value="revision">Minta revisi</button></div></form>
@endif
@if(auth()->user()->role==='sarpras' && $report->status==='Verified' && !$report->ticket)
<form method="POST" action="{{ route('reports.ticket',$report) }}" class="inline-action-form">@csrf<button type="submit" class="btn btn-primary btn-sm">Buat tiket maintenance →</button></form>
@endif
@if(auth()->user()->role==='pengguna' && $report->reporter_id===auth()->id() && $report->status==='Need Revision')
<form method="POST" action="{{ route('reports.revise',$report) }}" class="revise-form">@csrf @method('PUT')<div class="field"><label>Perbaiki deskripsi laporan</label><textarea name="description" rows="3" required>{{ old('description',$report->description) }}</textarea></div><button class="btn btn-primary btn-sm" type="submit">Kirim revisi</button></form>
@endif
@if($report->ticket)<div class="ticket-ref"><span>Ticket {{ $report->ticket->ticket_number }}</span><span class="badge {{ $report->ticket->status==='Completed'?'badge-green':'badge-blue' }}">{{ $report->ticket->status }}</span>@if($report->ticket->workOrder)<span>WO {{ $report->ticket->workOrder->work_order_number }}</span>@endif</div>@endif
</div>
@empty<div class="panel empty-state"><div class="empty-icon">◉</div><h3>Belum ada laporan kerusakan</h3><p class="muted">Laporan yang kamu buat atau yang masuk ke sistem akan muncul di sini.</p>@if(auth()->user()->role==='pengguna')<a class="btn btn-primary" href="{{ route('reports.create') }}">Buat laporan pertama</a>@endif</div>@endforelse
<div class="pagination-wrap">{{ $reports->links() }}</div>
@endsection
