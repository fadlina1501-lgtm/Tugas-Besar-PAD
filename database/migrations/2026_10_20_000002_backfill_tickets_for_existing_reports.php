<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('damage_reports') || !Schema::hasTable('maintenance_tickets')) {
            return;
        }

        $reportsWithoutTickets = DB::table('damage_reports')
            ->leftJoin('maintenance_tickets', 'maintenance_tickets.damage_report_id', '=', 'damage_reports.id')
            ->whereNull('maintenance_tickets.id')
            ->select('damage_reports.id', 'damage_reports.status', 'damage_reports.reported_at')
            ->orderBy('damage_reports.id')
            ->get();

        foreach ($reportsWithoutTickets as $report) {
            $openedAt = $report->reported_at
                ? Carbon::parse($report->reported_at)
                : now();

            DB::table('maintenance_tickets')->insert([
                'ticket_number' => 'TCK-MIG-'.$report->id.'-'.Str::upper(Str::random(4)),
                'damage_report_id' => $report->id,
                'status' => $report->status === 'Verified' ? 'Open' : 'Pending Verification',
                'opened_at' => $openedAt,
                'sla_deadline' => $openedAt->copy()->addHours(24),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally retain the generated tickets so a rollback never deletes
        // maintenance tracking records that may have been updated after migration.
    }
};
