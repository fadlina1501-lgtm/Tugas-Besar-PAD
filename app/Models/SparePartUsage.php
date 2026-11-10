<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SparePartUsage extends Pivot
{
    protected $table = 'work_order_spare_parts';

    protected $fillable = ['work_order_id', 'spare_part_id', 'quantity', 'unit_price'];

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function hitungSubtotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
