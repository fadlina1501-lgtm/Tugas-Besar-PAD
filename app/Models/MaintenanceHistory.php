<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceHistory extends Model
{
    protected $fillable = ['asset_id', 'work_order_id', 'downtime_minutes', 'total_cost', 'sla_breach', 'service_date'];
    protected function casts(): array { return ['total_cost' => 'decimal:2', 'sla_breach' => 'boolean', 'service_date' => 'datetime']; }
    public function asset() { return $this->belongsTo(Asset::class); }
    public function workOrder() { return $this->belongsTo(WorkOrder::class); }
}
