<?php

namespace Domain\Driver\Services;

use Domain\Driver\Actions\GetAvailableDriversAction;
use Domain\Driver\Actions\GetDriversAction;
use Domain\Driver\Actions\MarkDriverAvailableAction;
use Domain\Driver\Actions\MarkDriverUnavailableAction;
use Domain\Driver\Actions\UpdateDriverLocationAction;
use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Driver\DataTransferObjects\DriverLocationData;
use Domain\Driver\Exceptions\DriverNotFoundException;
use Domain\Driver\Models\Entities\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DriverService implements DriverServiceContract
{
    public function findOrFail(int $id): Driver
    {
        return Driver::find($id) ?? throw new DriverNotFoundException($id);
    }

    public function getDrivers(int $perPage = 15): LengthAwarePaginator
    {
        return (new GetDriversAction($perPage))->handle();
    }

    public function getAvailableDrivers(): Collection
    {
        return (new GetAvailableDriversAction)->handle();
    }

    public function updateLocation(int $driverId, DriverLocationData $data): void
    {
        (new UpdateDriverLocationAction($driverId, $data))->handle();
    }

    public function markUnavailable(int $driverId): void
    {
        (new MarkDriverUnavailableAction($driverId))->handle();
    }

    public function markAvailable(int $driverId): void
    {
        (new MarkDriverAvailableAction($driverId))->handle();
    }
}
