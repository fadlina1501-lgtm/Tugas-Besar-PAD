<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_code', 'name', 'condition', 'category_id', 'location_id',
        'purchase_date', 'purchase_price',
    ];

    protected function casts(): array
    {
        return ['purchase_date' => 'date', 'purchase_price' => 'decimal:2'];
    }

    public function category() { return $this->belongsTo(AssetCategory::class, 'category_id'); }
    public function location() { return $this->belongsTo(Location::class); }
    public function damageReports() { return $this->hasMany(DamageReport::class); }
    public function maintenanceHistories() { return $this->hasMany(MaintenanceHistory::class); }

    public function getTotalMaintenanceCostAttribute(): float
    {
        if (array_key_exists('maintenance_cost_sum', $this->attributes)) {
            return (float) $this->attributes['maintenance_cost_sum'];
        }
        return (float) $this->maintenanceHistories()->sum('total_cost');
    }

    public function getTotalMaintenanceCost(): float { return $this->total_maintenance_cost; }

    public function getTotalDowntimeAttribute(): int
    {
        if (array_key_exists('maintenance_downtime_sum', $this->attributes)) {
            return (int) $this->attributes['maintenance_downtime_sum'];
        }
        return (int) $this->maintenanceHistories()->sum('downtime_minutes');
    }

    public function getTotalDowntime(): int { return $this->total_downtime; }

    /** Number of maintenance incidents confirmed by Sarpras. */
    public function getFailureCountAttribute($value = null): int
    {
        if (array_key_exists('confirmed_failure_count', $this->attributes)) {
            return (int) $this->attributes['confirmed_failure_count'];
        }
        return (int) $this->damageReports()->where('status', 'Verified')->count();
    }

    /** Number of confirmed incidents recorded in the last 12 months. */
    public function getFailureFrequency(): int
    {
        return (int) $this->damageReports()
            ->where('status', 'Verified')
            ->where('reported_at', '>=', now()->subYear())
            ->count();
    }

    /** LCC estimate based on fields currently captured by the system. */   
    public function calculateLifeCycleCost(): float
    {
        return (float) ($this->purchase_price ?? 0) + $this->getTotalMaintenanceCost();
    }

    public function getLifeCycleCostAttribute(): float { return $this->calculateLifeCycleCost(); }


}
