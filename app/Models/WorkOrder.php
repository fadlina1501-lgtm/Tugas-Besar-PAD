<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'work_order_number', 'maintenance_ticket_id', 'maintenance_type_id', 'technician_id',
        'status', 'started_at', 'completed_at', 'labor_cost', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'labor_cost' => 'decimal:2',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }

    public function maintenanceType()
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function spareParts()
    {
        return $this->belongsToMany(SparePart::class, 'work_order_spare_parts')
            ->using(SparePartUsage::class)
            ->withPivot(['quantity', 'unit_price'])
            ->withTimestamps();
    }

    public function history()
    {
        return $this->hasOne(MaintenanceHistory::class);
    }

    /** Memperbarui status work order beserta atribut progresnya. */
    public function updateStatus(string $status, array $attributes = []): bool
    {
        return $this->fill(array_merge($attributes, ['status' => $status]))->save();
    }

    /** Menghitung downtime dari waktu mulai sampai waktu selesai. */
    public function hitungDowntime(): int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return 0;
        }

        return max(0, (int) $this->started_at->diffInMinutes($this->completed_at));
    }

    /** Menghitung total biaya tenaga kerja dan pemakaian suku cadang. */
    public function hitungTotalCost(): float
    {
        $parts = $this->relationLoaded('spareParts')
            ? $this->spareParts
            : $this->spareParts()->get();

        $sparePartCost = $parts->sum(
            fn ($part) => $part->pivot->hitungSubtotal()
        );

        return (float) $this->labor_cost + (float) $sparePartCost;
    }

    public function getSparePartCostAttribute(): float
    {
        $parts = $this->relationLoaded('spareParts')
            ? $this->spareParts
            : $this->spareParts()->get();

        return (float) $parts->sum(fn ($part) => $part->pivot->hitungSubtotal());
    }

    public function getTotalCostAttribute(): float
    {
        return $this->hitungTotalCost();
    }

    public function getDowntimeMinutesAttribute(): int
    {
        return $this->hitungDowntime();
    }
}
