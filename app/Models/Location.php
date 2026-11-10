<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'building',
        'floor',
        'room',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function getFloorCodeAttribute(): string
    {
        return 'B' . $this->floor;
    }

    public function getLabelAttribute(): string
    {
        return $this->building
            . ' • '
            . $this->floor_code
            . ' • '
            . $this->room;
    }
}
