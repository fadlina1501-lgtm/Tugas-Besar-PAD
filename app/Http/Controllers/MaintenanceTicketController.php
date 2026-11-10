<?php

namespace App\Http\Controllers;

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
        $tickets = MaintenanceTicket::with([
            'damageReport.asset.location',
            'damageReport.reporter',
            'workOrder.technician',
            'workOrder.maintenanceType',
        ])->latest()->paginate(12);

        $technicians = User::where('role', 'teknisi')->orderBy('name')->get();
        $types = MaintenanceType::orderBy('name')->get();

        return view('tickets.index', compact('tickets', 'technicians', 'types'));
    }

    public function createWorkOrder(Request $request, MaintenanceTicket $ticket)
    {
        $ticket->loadMissing('damageReport');

        abort_unless(
            $ticket->damageReport && $ticket->damageReport->status === 'Verified',
            422,
            'Laporan harus diverifikasi sebelum work order dibuat.'
        );
        abort_unless($ticket->status === 'Open', 422, 'Tiket harus berstatus Open sebelum work order dibuat.');
        abort_if($ticket->workOrder()->exists(), 422, 'Work order untuk tiket ini sudah dibuat.');

        $data = $request->validate([
            'maintenance_type_id' => ['required', 'exists:maintenance_types,id'],
            'technician_id' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(
            User::whereKey($data['technician_id'])->where('role', 'teknisi')->exists(),
            422,
            'Pengguna yang dipilih bukan teknisi.'
        );

        DB::transaction(function () use ($ticket, $data) {
            WorkOrder::create([
                'work_order_number' => 'WO-'.now()->format('ymd').'-'.Str::upper(Str::random(5)),
                'maintenance_ticket_id' => $ticket->id,
                'maintenance_type_id' => $data['maintenance_type_id'],
                'technician_id' => $data['technician_id'],
                'status' => 'Awaiting Acceptance',
                'labor_cost' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Status Assigned baru diberikan setelah teknisi menerima work order.
            $ticket->update(['status' => 'Awaiting Acceptance']);
        });

        return redirect()->route('tickets.index')
            ->with('success', 'Work order berhasil dibuat dan dikirim kepada teknisi untuk diterima.');
    }
}
