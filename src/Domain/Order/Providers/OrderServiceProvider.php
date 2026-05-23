<?php

namespace Domain\Order\Providers;

use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Models\Entities\Order;
use Domain\Order\Observers\OrderObserver;
use Domain\Order\Services\OrderService;
use Domain\Order\Services\RedisPendingOrderStore;
use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderServiceContract::class, OrderService::class);
        $this->app->bind(PendingOrderStoreContract::class, RedisPendingOrderStore::class);
    }

    public function boot(): void
    {
        Order::observe(OrderObserver::class);
    }
}
