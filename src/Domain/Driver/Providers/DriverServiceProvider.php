<?php

namespace Domain\Driver\Providers;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Driver\Models\Entities\DriverLocation;
use Domain\Driver\Observers\DriverLocationObserver;
use Domain\Driver\Services\DriverService;
use Domain\Driver\Services\RedisDriverLocationStore;
use Illuminate\Support\ServiceProvider;

class DriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DriverServiceContract::class, DriverService::class);
        $this->app->bind(DriverLocationStoreContract::class, RedisDriverLocationStore::class);
    }

    public function boot(): void
    {
        DriverLocation::observe(DriverLocationObserver::class);
    }
}
