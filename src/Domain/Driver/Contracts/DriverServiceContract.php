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
     * Returns all available drivers with vehicle and location eager loaded.
     * Used by the Assignment domain during the filter/score phase.
     */
    public function getAvailableDrivers(): Collection;

    public function updateLocation(int $driverId, DriverLocationData $data): void;

    public function markUnavailable(int $driverId): void;

    public function markAvailable(int $driverId): void;
}
