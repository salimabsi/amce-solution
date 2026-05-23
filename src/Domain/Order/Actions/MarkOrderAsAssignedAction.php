<?php

namespace Domain\Order\Actions;

use Domain\Order\Enums\OrderStatus;
use Domain\Order\Exceptions\OrderAlreadyAssignedException;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\Actions\Action;

class MarkOrderAsAssignedAction extends Action
{
    public function __construct(
        private readonly int $orderId,
        private readonly int $driverId,
    ) {}

    public function handle(): Order
    {
        $affected = Order::where('id', $this->orderId)
            ->where('status', OrderStatus::Pending)
            ->update([
                'status' => OrderStatus::Assigned,
                'driver_id' => $this->driverId,
                'assigned_at' => now(),
            ]);

        if ($affected === 0) {
            throw new OrderAlreadyAssignedException($this->orderId);
        }

        return Order::findOrFail($this->orderId);
    }
}
