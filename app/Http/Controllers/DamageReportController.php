<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DamageReport;
use App\Models\MaintenanceTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DamageReportController extends Controller
{
    public function index()
    {
        $reports = DamageReport::with(['asset.category', 'asset.location', 'reporter', 'verifier', 'ticket.workOrder'])
            ->when(auth()->user()->role === 'pengguna', fn ($query) => $query->where('reporter_id', auth()->id()))
            ->latest('reported_at')->paginate(12);
        return view('reports.index', compact('reports'));
    }

    public function create()
    {
        $assets = Asset::with('location')->orderBy('asset_code')->get();
        return view('reports.create', compact('assets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        DB::transaction(function () use ($data) {
            $reportedAt = now();
            $report = DamageReport::create([
                'report_number' => 'DR-'.$reportedAt->format('Ymd').'-'.Str::upper(Str::random(5)),
                'reporter_id' => auth()->id(),
                'asset_id' => $data['asset_id'],
                'description' => $data['description'],
                'reported_at' => $reportedAt,
                'status' => 'Submitted',
            ]);

            // Workflow final: each report immediately receives a ticket awaiting verification.
            MaintenanceTicket::create([
                'ticket_number' => 'TCK-'.$reportedAt->format('ymd').'-'.Str::upper(Str::random(5)),
                'damage_report_id' => $report->id,
                'status' => 'Pending Verification',
                'opened_at' => $reportedAt,
                'sla_deadline' => $reportedAt->copy()->addHours(24),
            ]);
        });

        return redirect()->route('reports.index')->with('success', 'Laporan dan tiket maintenance berhasil dibuat. Tiket menunggu verifikasi Sarpras.');
    }

    public function verify(Request $request, DamageReport $report)
    {
        abort_unless(in_array($report->status, ['Submitted', 'Revised'], true), 422, 'Hanya laporan baru atau laporan revisi yang dapat diverifikasi.');
        $data = $request->validate([
            'decision' => ['required', 'in:verified,revision'],
            'verification_note' => ['nullable', 'string', 'max:1500'],
        ]);

        DB::transaction(function () use ($report, $data) {
            $verified = $data['decision'] === 'verified';
            $report->update([
                'status' => $verified ? 'Verified' : 'Need Revision',
                'verified_by' => auth()->id(),
                'verification_note' => $data['verification_note'] ?? null,
            ]);
            // Null-safe for older reports that existed before tickets were automatic.
            $ticket = $report->ticket()->first();
            if ($ticket) {
                $ticket->update(['status' => $verified ? 'Open' : 'Pending Verification']);
            }
        });

        return back()->with('success', $data['decision'] === 'verified'
            ? 'Laporan diverifikasi. Tiket masuk antrean Sarpras untuk pembuatan work order.'
            : 'Laporan dikembalikan kepada pelapor untuk revisi.');
    }

    public function revise(Request $request, DamageReport $report)
    {
        abort_unless($report->reporter_id === auth()->id(), 403);
        abort_unless($report->status === 'Need Revision', 403, 'Laporan belum membutuhkan revisi.');
        $data = $request->validate(['description' => ['required', 'string', 'min:10', 'max:3000']]);

        DB::transaction(function () use ($report, $data) {
            $report->update([
                'description' => $data['description'],
                'status' => 'Revised',
                'verification_note' => null,
                'verified_by' => null,
            ]);
            $report->ticket()->update(['status' => 'Pending Verification']);
        });

        return back()->with('success', 'Laporan revisi berhasil dikirim kembali untuk diverifikasi Sarpras.');
    }
}
