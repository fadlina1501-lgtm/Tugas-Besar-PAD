<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceTicket extends Model
{
    protected $fillable = ['ticket_number', 'damage_report_id', 'status', 'opened_at', 'sla_deadline'];
    protected function casts(): array { return ['opened_at' => 'datetime', 'sla_deadline' => 'datetime']; }
    public function damageReport() { return $this->belongsTo(DamageReport::class); }
    public function workOrder() { return $this->hasOne(WorkOrder::class); }
    public function getIsSlaBreachedAttribute(): bool
    {
        if (!$this->sla_deadline) return false;
        $completedAt = $this->workOrder?->completed_at;
        if ($completedAt) return $completedAt->gt($this->sla_deadline);
        return $this->status !== 'Completed' && now()->gt($this->sla_deadline);
    }
}
