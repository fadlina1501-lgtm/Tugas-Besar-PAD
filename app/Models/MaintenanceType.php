<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MaintenanceType extends Model
{
    protected $fillable = ['name', 'description'];
    public function workOrders() { return $this->hasMany(WorkOrder::class); }
}
