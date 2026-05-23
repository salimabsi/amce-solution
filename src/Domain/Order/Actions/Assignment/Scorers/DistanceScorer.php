<?php

namespace Domain\Order\Actions\Assignment\Scorers;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Contracts\DriverScorerContract;
use Domain\Order\Models\Entities\Order;

class DistanceScorer implements DriverScorerContract
{
    public function score(Driver $driver, Order $order): float
    {
        if (! $driver->location) {
            return 0;
        }

        $distanceKm = $this->haversineKm(
            $driver->location->lat,
            $driver->location->lng,
            $order->pickup_lat,
            $order->pickup_lng,
        );

        // Closer drivers score higher; +1 avoids division by zero
        return 1 / ($distanceKm + 1);
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }
}
