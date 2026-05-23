<?php

namespace Domain\Driver\Services;

use Domain\Driver\Actions\GetAvailableDriversNearbyAction;
use Domain\Driver\Actions\GetDriversAction;
use Domain\Driver\Actions\MarkDriverAvailableAction;
use Domain\Driver\Actions\MarkDriverUnavailableAction;
use Domain\Driver\Actions\UpdateDriverLocationAction;
use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Driver\DataTransferObjects\DriverLocationData;
use Domain\Driver\Exceptions\DriverNotFoundException;
use Domain\Driver\Models\Entities\Driver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DriverService implements DriverServiceContract
{
    public function __construct(private readonly DriverLocationStoreContract $locationStore) {}

    public function findOrFail(int $id): Driver
    {
        return Driver::find($id) ?? throw new DriverNotFoundException($id);
    }

    public function getDrivers(int $perPage = 15): LengthAwarePaginator
    {
        return (new GetDriversAction($perPage))->handle();
    }

    public function getAvailableDriversNearby(float $lat, float $lng, float $radiusKm): Collection
    {
        return (new GetAvailableDriversNearbyAction($this->locationStore, $lat, $lng, $radiusKm))->handle();
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
