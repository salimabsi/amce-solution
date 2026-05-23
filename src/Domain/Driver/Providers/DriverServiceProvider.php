<?php

namespace Domain\Driver\Providers;

use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Driver\Services\DriverService;
use Illuminate\Support\ServiceProvider;

class DriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DriverServiceContract::class, DriverService::class);
    }

    public function boot(): void
    {
        //
    }
}
