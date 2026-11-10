<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceHistory;
use App\Models\SparePart;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $workOrders = WorkOrder::with([
            'ticket.damageReport.asset.location',
            'ticket.damageReport.reporter',
            'technician',
            'maintenanceType',
            'spareParts',
            'history',
        ])
            ->when($user->role === 'teknisi', fn ($query) => $query->where('technician_id', $user->id))
            ->latest()
            ->paginate(12);

        // Daftar komponen hanya dipakai teknisi untuk mencatat pemakaian aktual.
        $parts = $user->role === 'teknisi'
            ? SparePart::orderBy('name')->get()
            : collect();

        return view('work-orders.index', compact('workOrders', 'parts'));
    }

    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeTechnicianAssignment($workOrder);
        abort_unless($workOrder->status !== 'Completed', 422, 'Work order yang sudah selesai tidak dapat diubah kembali.');

        $data = $request->validate([
            'status' => ['required', 'in:In Progress,Completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['status'] === 'Completed' && ! $workOrder->started_at) {
            return back()->with('error', 'Mulai pekerjaan terlebih dahulu sebelum menyelesaikan work order.');
        }

        DB::transaction(function () use ($workOrder, $data) {
            $updates = ['status' => $data['status']];

            if (array_key_exists('notes', $data)) {
                $updates['notes'] = $data['notes'];
            }

            if ($data['status'] === 'In Progress' && ! $workOrder->started_at) {
                $updates['started_at'] = now();
            }

            if ($data['status'] === 'Completed') {
                $updates['completed_at'] = now();
            }

            $workOrder->update($updates);
            $workOrder->ticket()->update(['status' => $data['status']]);

            if ($data['status'] === 'Completed') {
                $workOrder->load(['spareParts', 'ticket.damageReport.asset']);

                $downtime = $workOrder->started_at
                    ? max(0, (int) $workOrder->started_at->diffInMinutes($workOrder->completed_at))
                    : 0;
                $spareCost = $workOrder->spareParts->sum(
                    fn ($part) => $part->pivot->quantity * $part->pivot->unit_price
                );
                $totalCost = (float) $workOrder->labor_cost + (float) $spareCost;
                $breach = $workOrder->ticket->sla_deadline
                    && $workOrder->completed_at->gt($workOrder->ticket->sla_deadline);

                MaintenanceHistory::updateOrCreate(
                    ['work_order_id' => $workOrder->id],
                    [
                        'asset_id' => $workOrder->ticket->damageReport->asset_id,
                        'downtime_minutes' => $downtime,
                        'total_cost' => $totalCost,
                        'sla_breach' => $breach,
                        'service_date' => $workOrder->completed_at,
                    ]
                );

                $workOrder->ticket->update(['status' => 'Completed']);
                $workOrder->ticket->damageReport->asset->update(['condition' => 'Baik']);
            }
        });

        return back()->with('success', 'Status work order berhasil diperbarui.');
    }

    public function updateCosts(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeTechnicianAssignment($workOrder);
        abort_unless($workOrder->status !== 'Completed', 422, 'Biaya work order yang sudah selesai tidak dapat diubah.');

        $data = $request->validate([
            'labor_cost' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        $workOrder->update(['labor_cost' => $data['labor_cost']]);

        return back()->with('success', 'Biaya tenaga kerja aktual berhasil dicatat.');
    }

    public function addPart(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeTechnicianAssignment($workOrder);
        abort_unless($workOrder->status !== 'Completed', 422, 'Suku cadang tidak dapat ditambahkan ke pekerjaan yang sudah selesai.');

        $data = $request->validate([
            'spare_part_id' => ['required', 'exists:spare_parts,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        DB::transaction(function () use ($workOrder, $data) {
            $part = SparePart::whereKey($data['spare_part_id'])->firstOrFail();
            $pivot = DB::table('work_order_spare_parts')
                ->where('work_order_id', $workOrder->id)
                ->where('spare_part_id', $part->id)
                ->first();

            if ($pivot) {
                $combinedQuantity = (int) $pivot->quantity + (int) $data['quantity'];
                $combinedUnitPrice = (
                    ((int) $pivot->quantity * (float) $pivot->unit_price)
                    + ((int) $data['quantity'] * (float) $data['unit_price'])
                ) / $combinedQuantity;

                DB::table('work_order_spare_parts')
                    ->where('id', $pivot->id)
                    ->update([
                        'quantity' => $combinedQuantity,
                        'unit_price' => $combinedUnitPrice,
                        'updated_at' => now(),
                    ]);
            } else {
                $workOrder->spareParts()->attach($part->id, [
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                ]);
            }
        });

        return back()->with('success', 'Komponen, jumlah pemakaian, dan harga aktual berhasil dicatat.');
    }

    private function authorizeTechnicianAssignment(WorkOrder $workOrder): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->role === 'teknisi' && $workOrder->technician_id === $user->id,
            403,
            'Hanya teknisi yang ditugaskan yang dapat memperbarui work order ini.'
        );
    }
}
