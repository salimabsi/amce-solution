<?php

namespace Domain\Order\Actions\Assignment\Filters;

use Domain\Order\Contracts\DriverFilterContract;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Collection;

class VehicleCapacityFilter implements DriverFilterContract
{
    public function filter(Collection $drivers, Order $order): Collection
    {
        return $drivers->filter(
            fn ($driver) => $driver->vehicle && $driver->vehicle->capacity_kg >= $order->weight_kg
        )->values();
    }
}
