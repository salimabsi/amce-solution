<?php

namespace Domain\Driver\Models\Entities;

use Domain\Driver\Enums\VehicleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'type',
        'capacity_kg',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'capacity_kg' => 'decimal:2',
        ];
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }
}
