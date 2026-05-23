<?php

namespace Domain\Order\Contracts;

use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Models\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderServiceContract
{
    public function findOrFail(int $id): Order;

    public function getPendingOrders(int $perPage = 15): LengthAwarePaginator;

    public function getDriverOrders(int $driverId, OrderFilterData $filters): LengthAwarePaginator;

    /**
     * Atomically marks an order as assigned to a driver.
     * Throws OrderAlreadyAssignedException if the order is no longer pending.
     */
    public function markAsAssigned(int $orderId, int $driverId): Order;

    /**
     * Runs the full filter → score pipeline and assigns the best available driver.
     * Throws NoAvailableDriverException if no driver passes the filters.
     */
    public function assignOrder(int $orderId): Order;
}
