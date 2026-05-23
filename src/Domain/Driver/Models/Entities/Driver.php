<?php

namespace Domain\Driver\Models\Entities;

use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'is_available',
        'vehicle_id',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location(): HasOne
    {
        return $this->hasOne(DriverLocation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
