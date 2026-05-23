<?php

namespace Domain\Order\Actions\Assignment\Scorers;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Contracts\DriverScorerContract;
use Domain\Order\Models\Entities\Order;

class VehicleCapacityFitScorer implements DriverScorerContract
{
    public function score(Driver $driver, Order $order): float
    {
        if (! $driver->vehicle || $driver->vehicle->capacity_kg <= 0) {
            return 0;
        }

        // Higher ratio = tighter fit = better score (truck carrying 10kg scores poorly)
        return $order->weight_kg / $driver->vehicle->capacity_kg;
    }
}
