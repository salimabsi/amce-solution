<?php

namespace Domain\Order\Observers;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;

class OrderObserver
{
    public function __construct(private readonly PendingOrderStoreContract $store) {}

    public function saved(Order $order): void
    {
        if ($order->status === OrderStatus::Pending) {
            $this->store->add($order->id, $order->created_at->timestamp);
        } else {
            $this->store->remove($order->id);
        }
    }

    public function deleted(Order $order): void
    {
        $this->store->remove($order->id);
    }
}
