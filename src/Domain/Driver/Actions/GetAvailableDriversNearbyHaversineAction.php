<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;
use Illuminate\Database\Eloquent\Collection;

/**
 * BENCHMARK-ONLY: the original PHP-Haversine path.
 * Loads ALL available drivers from DB, then filters by distance in PHP.
 * Kept for A/B comparison against GetAvailableDriversNearbyAction (Redis GEO).
 * See benchmarks/SUMMARY.md.
 */
class GetAvailableDriversNearbyHaversineAction extends Action
{
    public function __construct(
        private readonly float $lat,
        private readonly float $lng,
        private readonly float $radiusKm,
    ) {}

    public function handle(): Collection
    {
        $drivers = Driver::query()
            ->where('is_available', true)
            ->with(['vehicle', 'location'])
            ->get();

        return $drivers->filter(function (Driver $driver) {
            if (! $driver->location) {
                return false;
            }

            return $this->haversineKm(
                (float) $driver->location->lat,
                (float) $driver->location->lng,
                $this->lat,
                $this->lng,
            ) <= $this->radiusKm;
        })->values();
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(sqrt($a));
    }
}
