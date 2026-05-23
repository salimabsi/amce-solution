<?php

namespace Domain\Order\Contracts;

use Domain\Order\DataTransferObjects\CreateOrderData;
use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Models\Entities\Order;
use Domain\Order\Models\Entities\UnprocessedOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderServiceContract
{
    public function findOrFail(int $id): Order;

    public function getPendingOrders(int $perPage = 15): LengthAwarePaginator;

    public function getDriverOrders(int $driverId, OrderFilterData $filters): LengthAwarePaginator;

    /**
     * Stages a new order in unprocessed_orders for the worker to pick up.
     * Returns 202-style: the caller knows it was accepted, not yet visible to ops.
     */
    public function queueForProcessing(CreateOrderData $data): UnprocessedOrder;

    /**
     * Drains a batch of unprocessed orders into the orders table and Redis ZSET.
     * Returns the number of orders processed in this batch.
     */
    public function processUnprocessedBatch(int $batchSize = 500): int;

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
