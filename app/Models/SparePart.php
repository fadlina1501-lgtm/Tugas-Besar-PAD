<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SparePart extends Model
{
    protected $fillable = ['sku', 'name', 'unit_price', 'stock', 'unit'];
    protected function casts(): array { return ['unit_price' => 'decimal:2', 'stock' => 'integer']; }
    public function workOrders() { return $this->belongsToMany(WorkOrder::class, 'work_order_spare_parts')->withPivot(['quantity', 'unit_price'])->withTimestamps(); }
}
