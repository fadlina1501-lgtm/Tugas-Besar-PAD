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
        $workOrders = WorkOrder::with([
            'ticket.damageReport.asset.location',
            'ticket.damageReport.reporter',
            'technician',
            'maintenanceType',
            'spareParts',
            'history',
        ])
            ->where('technician_id', auth()->id())
            ->latest()
            ->paginate(12);

        $parts = SparePart::where('stock', '>', 0)->orderBy('name')->get();

        return view('work-orders.index', compact('workOrders', 'parts'));
    }

    /** Teknisi menerima penugasan dari Sarpras sebelum pekerjaan dimulai. */
    public function accept(WorkOrder $workOrder)
    {
        $this->authorizeAssignedTechnician($workOrder);
        abort_unless(
            $workOrder->status === 'Awaiting Acceptance',
            422,
            'Work order ini tidak sedang menunggu penerimaan.'
        );

        DB::transaction(function () use ($workOrder) {
            $workOrder->update(['status' => 'Assigned']);
            $workOrder->ticket()->update(['status' => 'Assigned']);
        });

        return back()->with('success', 'Work order berhasil diterima. Pekerjaan dapat dimulai.');
    }

    /** Teknisi memulai pekerjaan, mencatat progres/biaya, dan menyelesaikan WO. */
    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeAssignedTechnician($workOrder);
        abort_unless($workOrder->status !== 'Completed', 422, 'Work order yang sudah selesai tidak dapat diubah kembali.');

        $data = $request->validate([
            'status' => ['required', 'in:In Progress,Completed'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ]);

        if ($data['status'] === 'In Progress') {
            abort_unless(
                in_array($workOrder->status, ['Assigned', 'In Progress'], true),
                422,
                'Terima work order terlebih dahulu sebelum memulai perbaikan.'
            );
        }

        if ($data['status'] === 'Completed') {
            abort_unless(
                $workOrder->status === 'In Progress' && $workOrder->started_at,
                422,
                'Pekerjaan harus dimulai sebelum dapat diselesaikan.'
            );
        }

        DB::transaction(function () use ($workOrder, $data) {
            $updates = ['status' => $data['status']];

            if (array_key_exists('notes', $data)) {
                $updates['notes'] = $data['notes'];
            }

            if (array_key_exists('labor_cost', $data) && $data['labor_cost'] !== null) {
                $updates['labor_cost'] = $data['labor_cost'];
            }

            if ($data['status'] === 'In Progress' && ! $workOrder->started_at) {
                $updates['started_at'] = now();
            }

            if ($data['status'] === 'Completed') {
                $updates['completed_at'] = now();
            }

            $workOrder->updateStatus($data['status'], $updates);
            $workOrder->ticket()->update(['status' => $data['status']]);

            if ($data['status'] === 'Completed') {
                $workOrder->load(['spareParts', 'ticket.damageReport.asset', 'ticket']);

                $completedAt = $workOrder->completed_at;
                $downtime = $workOrder->hitungDowntime();
                $totalCost = $workOrder->hitungTotalCost();
                $slaBreach = $workOrder->ticket->sla_deadline
                    && $completedAt->gt($workOrder->ticket->sla_deadline);

                // Sistem menyimpan histori, downtime, biaya, dan pencapaian SLA.
                $history = MaintenanceHistory::firstOrNew(['work_order_id' => $workOrder->id]);
                $history->fill([
                    'asset_id' => $workOrder->ticket->damageReport->asset_id,
                    'downtime_minutes' => $downtime,
                    'total_cost' => $totalCost,
                    'sla_breach' => (bool) $slaBreach,
                    'service_date' => $completedAt,
                ]);
                $history->simpan();

                // Kondisi aset diperbarui setelah proses perbaikan dinyatakan selesai.
                $workOrder->ticket->damageReport->asset->update(['condition' => 'Baik']);
            }
        });

        return back()->with(
            'success',
            $data['status'] === 'Completed'
                ? 'Perbaikan selesai. Histori maintenance dan perhitungan biaya berhasil disimpan.'
                : 'Progres perbaikan berhasil diperbarui.'
        );
    }

    /** Teknisi mencatat kebutuhan/pemakaian suku cadang selama perbaikan. */
    public function addPart(Request $request, WorkOrder $workOrder)
    {
        $this->authorizeAssignedTechnician($workOrder);
        abort_unless(
            $workOrder->status === 'In Progress',
            422,
            'Suku cadang hanya dapat dicatat saat perbaikan sedang berlangsung.'
        );

        $data = $request->validate([
            'spare_part_id' => ['required', 'exists:spare_parts,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($workOrder, $data) {
            $part = SparePart::whereKey($data['spare_part_id'])->lockForUpdate()->firstOrFail();
            abort_if($part->stock < $data['quantity'], 422, 'Stok suku cadang tidak mencukupi.');

            $pivot = DB::table('work_order_spare_parts')
                ->where('work_order_id', $workOrder->id)
                ->where('spare_part_id', $part->id)
                ->first();

            if ($pivot) {
                DB::table('work_order_spare_parts')
                    ->where('id', $pivot->id)
                    ->update([
                        'quantity' => $pivot->quantity + $data['quantity'],
                        'updated_at' => now(),
                    ]);
            } else {
                $workOrder->spareParts()->attach($part->id, [
                    'quantity' => $data['quantity'],
                    'unit_price' => $part->unit_price,
                ]);
            }

            $part->decrement('stock', $data['quantity']);
        });

        return back()->with('success', 'Pemakaian suku cadang berhasil dicatat.');
    }

    private function authorizeAssignedTechnician(WorkOrder $workOrder): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->role === 'teknisi' && $workOrder->technician_id === $user->id,
            403,
            'Work order hanya dapat diproses oleh teknisi yang ditugaskan.'
        );
    }
}
