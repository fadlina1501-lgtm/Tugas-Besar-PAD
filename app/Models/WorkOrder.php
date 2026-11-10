<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WorkOrder extends Model
{
    protected $fillable = ['work_order_number', 'maintenance_ticket_id', 'maintenance_type_id', 'technician_id', 'status', 'started_at', 'completed_at', 'labor_cost', 'notes'];
    protected function casts(): array { return ['started_at' => 'datetime', 'completed_at' => 'datetime', 'labor_cost' => 'decimal:2']; }
    public function ticket() { return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id'); }
    public function maintenanceType() { return $this->belongsTo(MaintenanceType::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function spareParts() { return $this->belongsToMany(SparePart::class, 'work_order_spare_parts')->withPivot(['quantity', 'unit_price'])->withTimestamps(); }
    public function history() { return $this->hasOne(MaintenanceHistory::class); }
    public function getSparePartCostAttribute(): float { return (float) $this->spareParts->sum(fn ($part) => $part->pivot->quantity * $part->pivot->unit_price); }
    public function getTotalCostAttribute(): float { return (float) $this->labor_cost + $this->spare_part_cost; }
    public function getDowntimeMinutesAttribute(): int { return $this->started_at && $this->completed_at ? max(0, (int) $this->started_at->diffInMinutes($this->completed_at, false)) : 0; }
}
