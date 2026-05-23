<?php

namespace Domain\Order\Providers;

use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\Services\OrderService;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceContract::class, OrderService::class);
    }

    public function boot(): void
    {
        //
    }
}
