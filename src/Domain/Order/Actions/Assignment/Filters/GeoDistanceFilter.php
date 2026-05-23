<?php

namespace Domain\Order\Actions\Assignment\Filters;

use Domain\Order\Contracts\DriverFilterContract;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Collection;

class GeoDistanceFilter implements DriverFilterContract
{
    private const MAX_RADIUS_KM = 50;

    public function filter(Collection $drivers, Order $order): Collection
    {
        return $drivers->filter(function ($driver) use ($order) {
            if (! $driver->location) {
                return false;
            }

            return $this->haversineKm(
                $driver->location->lat,
                $driver->location->lng,
                $order->pickup_lat,
                $order->pickup_lng,
            ) <= self::MAX_RADIUS_KM;
        })->values();
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
