<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MaintenanceTicket extends Model
{
    protected $fillable = ['ticket_number', 'damage_report_id', 'status', 'opened_at', 'sla_deadline'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'sla_deadline' => 'datetime'];
    }

    public function damageReport()
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class);
    }

    /** Sistem membuat tiket otomatis setelah laporan kerusakan dikirim. */
    public static function buatDariLaporan(DamageReport $report): self
    {
        $openedAt = now();

        return self::create([
            'ticket_number' => 'TCK-'.$openedAt->format('ymd').'-'.Str::upper(Str::random(5)),
            'damage_report_id' => $report->id,
            'status' => 'Pending Verification',
            'opened_at' => $openedAt,
            'sla_deadline' => $openedAt->copy()->addHours(24),
        ]);
    }

    /** Memeriksa apakah tiket melampaui batas SLA. */
    public function cekSLA(): bool
    {
        if (! $this->sla_deadline) {
            return false;
        }

        $completedAt = $this->workOrder?->completed_at;
        if ($completedAt) {
            return $completedAt->gt($this->sla_deadline);
        }

        return $this->status !== 'Completed' && now()->gt($this->sla_deadline);
    }

    public function getIsSlaBreachedAttribute(): bool
    {
        return $this->cekSLA();
    }
}
