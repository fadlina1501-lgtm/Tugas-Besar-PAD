<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\MaintenanceTicket;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaintenanceTicketController extends Controller
{
    public function index()
    {
        $tickets = MaintenanceTicket::with(['damageReport.asset', 'damageReport.reporter', 'workOrder.technician', 'workOrder.maintenanceType'])
            ->latest()->paginate(12);
        $technicians = User::where('role', 'teknisi')->orderBy('name')->get();
        $types = MaintenanceType::orderBy('name')->get();
        return view('tickets.index', compact('tickets', 'technicians', 'types'));
    }

    /** Fallback for reports created before automatic ticket creation was implemented. */
    public function storeFromReport(DamageReport $report)
    {
        abort_unless($report->status === 'Verified', 422, 'Laporan harus diverifikasi terlebih dahulu.');
        if ($report->ticket()->exists()) {
            return back()->with('error', 'Tiket untuk laporan ini sudah dibuat otomatis.');
        }

        $openedAt = now();
        MaintenanceTicket::create([
            'ticket_number' => 'TCK-'.$openedAt->format('ymd').'-'.Str::upper(Str::random(5)),
            'damage_report_id' => $report->id,
            'status' => 'Open',
            'opened_at' => $openedAt,
            'sla_deadline' => $openedAt->copy()->addHours(24),
        ]);
        return redirect()->route('tickets.index')->with('success', 'Tiket untuk laporan lama berhasil dibuat.');
    }

    public function createWorkOrder(Request $request, MaintenanceTicket $ticket)
    {
        $ticket->loadMissing('damageReport');
        abort_unless($ticket->damageReport && $ticket->damageReport->status === 'Verified', 422, 'Laporan harus diverifikasi sebelum work order dibuat.');
        abort_unless($ticket->status === 'Open', 422, 'Tiket harus berstatus Open sebelum work order dibuat.');
        abort_if($ticket->workOrder()->exists(), 422, 'Work order untuk tiket ini sudah dibuat.');

        $data = $request->validate([
            'maintenance_type_id' => ['required', 'exists:maintenance_types,id'],
            'technician_id' => ['required', 'exists:users,id'],
            'labor_cost' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_unless(User::whereKey($data['technician_id'])->where('role', 'teknisi')->exists(), 422, 'Pengguna yang dipilih bukan teknisi.');

        DB::transaction(function () use ($ticket, $data) {
            WorkOrder::create([
                'work_order_number' => 'WO-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'maintenance_ticket_id' => $ticket->id,
                'maintenance_type_id' => $data['maintenance_type_id'],
                'technician_id' => $data['technician_id'],
                'status' => 'Assigned',
                'labor_cost' => $data['labor_cost'],
                'notes' => $data['notes'] ?? null,
            ]);
            // The status stays Assigned until the technician actually starts working.
            $ticket->update(['status' => 'Assigned']);
        });

        return redirect()->route('work-orders.index')->with('success', 'Work order dibuat dan ditugaskan kepada teknisi.');
    }
}
