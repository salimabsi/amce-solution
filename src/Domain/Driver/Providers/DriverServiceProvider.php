<?php

namespace Domain\Driver\Providers;

use Illuminate\Support\ServiceProvider;

class DriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Contract → Service bindings registered here in Phase 3
    }

    public function boot(): void
    {
        //
    }
}
