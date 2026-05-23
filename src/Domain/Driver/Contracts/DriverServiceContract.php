<?php

namespace Domain\Driver\Contracts;

use Domain\Driver\DataTransferObjects\DriverLocationData;
use Domain\Driver\Models\Entities\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DriverServiceContract
{
    public function findOrFail(int $id): Driver;

    public function getDrivers(int $perPage = 15): LengthAwarePaginator;

    /**
     * Returns available drivers within the given radius (km) of a point.
     * Uses Redis GEO for the proximity lookup, then hydrates from DB.
     */
    public function getAvailableDriversNearby(float $lat, float $lng, float $radiusKm): Collection;

    public function updateLocation(int $driverId, DriverLocationData $data): void;

    public function markUnavailable(int $driverId): void;

    public function markAvailable(int $driverId): void;
}
