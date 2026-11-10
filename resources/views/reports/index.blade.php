@extends('layouts.app')

@section('title', 'Laporan Kerusakan')
@section('page-title', 'Laporan Kerusakan')

@section('content')
<div class="page-intro">
    <div>
        <h2>{{ auth()->user()->role === 'pengguna' ? 'Laporan saya' : 'Pemeriksaan laporan' }}</h2>
        <p class="muted">
            {{ auth()->user()->role === 'pengguna'
                ? 'Buat laporan kerusakan, lakukan revisi bila diminta, dan pantau status perbaikannya.'
                : 'Periksa laporan kerusakan yang masuk dan minta revisi apabila informasi belum sesuai.' }}
        </p>
    </div>

    @if(auth()->user()->role === 'pengguna')
        <a href="{{ route('reports.create') }}" class="btn btn-primary">＋ Laporan baru</a>
    @endif
</div>

@forelse($reports as $report)
    <div class="panel report-card">
        <div class="report-card-head">
            <div>
                <span class="panel-tag">{{ $report->report_number }}</span>
                <h3>
                    {{ $report->asset->name }}
                    <span class="muted inline-muted">· {{ $report->asset->asset_code }}</span>
                </h3>
                <p class="muted">
                    {{ $report->reported_at->format('d M Y, H:i') }}
                    · {{ $report->asset->location->label }}
                </p>
            </div>
            <span class="badge {{ $report->status === 'Verified' ? 'badge-green' : ($report->status === 'Need Revision' ? 'badge-amber' : 'badge-blue') }}">
                {{ $report->status }}
            </span>
        </div>

        <div class="report-description">{{ $report->description }}</div>

        @if(auth()->user()->role === 'sarpras')
            <div class="report-meta">
                <span>Dilaporkan oleh <strong>{{ $report->reporter->name }}</strong></span>
            </div>
        @endif

        @if($report->verification_note)
            <div class="note-box">
                <strong>Catatan Sarpras</strong>
                <p>{{ $report->verification_note }}</p>
            </div>
        @endif

        @if(auth()->user()->role === 'sarpras' && in_array($report->status, ['Submitted', 'Revised'], true))
            <form method="POST" action="{{ route('reports.verify', $report) }}" class="review-form">
                @csrf
                <div class="field">
                    <label>Catatan pemeriksaan (opsional)</label>
                    <input name="verification_note" placeholder="Jelaskan informasi yang perlu dilengkapi jika meminta revisi">
                </div>
                <div class="action-row">
                    <button class="btn btn-primary btn-sm" name="decision" value="verified">✓ Verifikasi laporan</button>
                    <button class="btn btn-outline btn-sm" name="decision" value="revision">Minta revisi</button>
                </div>
            </form>
        @endif

        @if(auth()->user()->role === 'pengguna' && $report->reporter_id === auth()->id() && $report->status === 'Need Revision')
            <form method="POST" action="{{ route('reports.revise', $report) }}" class="revise-form">
                @csrf
                @method('PUT')
                <div class="field">
                    <label>Perbaiki deskripsi laporan</label>
                    <textarea name="description" rows="3" required>{{ old('description', $report->description) }}</textarea>
                </div>
                <button class="btn btn-primary btn-sm" type="submit">Kirim revisi</button>
            </form>
        @endif

        @if($report->ticket)
            <div class="ticket-ref">
                <span>Ticket {{ $report->ticket->ticket_number }}</span>
                <span class="badge {{ $report->ticket->status === 'Completed' ? 'badge-green' : 'badge-blue' }}">
                    Status perbaikan: {{ $report->ticket->status }}
                </span>
                @if($report->ticket->workOrder)
                    <span>Work Order {{ $report->ticket->workOrder->work_order_number }}</span>
                @endif
            </div>
        @endif
    </div>
@empty
    <div class="panel empty-state">
        <div class="empty-icon">◉</div>
        <h3>Belum ada laporan kerusakan</h3>
        <p class="muted">
            {{ auth()->user()->role === 'pengguna'
                ? 'Laporan yang kamu buat akan muncul di sini.'
                : 'Laporan kerusakan yang dikirim pengguna akan muncul di sini.' }}
        </p>
        @if(auth()->user()->role === 'pengguna')
            <a class="btn btn-primary" href="{{ route('reports.create') }}">Buat laporan pertama</a>
        @endif
    </div>
@endforelse

<div class="pagination-wrap">{{ $reports->links() }}</div>
@endsection
