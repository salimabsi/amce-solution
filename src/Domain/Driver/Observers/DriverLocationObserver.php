<?php

namespace Domain\Driver\Observers;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Models\Entities\DriverLocation;

class DriverLocationObserver
{
    public function __construct(private readonly DriverLocationStoreContract $store) {}

    public function saved(DriverLocation $location): void
    {
        $this->store->set($location->driver_id, (float) $location->lat, (float) $location->lng);
    }

    public function deleted(DriverLocation $location): void
    {
        $this->store->remove($location->driver_id);
    }
}
