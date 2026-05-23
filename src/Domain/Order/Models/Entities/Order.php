<?php

namespace Domain\Order\Models\Entities;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderPriority;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Enums\OrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'type',
        'priority',
        'weight_kg',
        'pickup_lat',
        'pickup_lng',
        'dropoff_lat',
        'dropoff_lng',
        'driver_id',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'type' => OrderType::class,
            'priority' => OrderPriority::class,
            'weight_kg' => 'decimal:2',
            'pickup_lat' => 'decimal:7',
            'pickup_lng' => 'decimal:7',
            'dropoff_lat' => 'decimal:7',
            'dropoff_lng' => 'decimal:7',
            'assigned_at' => 'datetime',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
