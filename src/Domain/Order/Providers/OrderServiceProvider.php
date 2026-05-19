<?php

namespace Domain\Order\Providers;

use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
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
