<?php

namespace Domain\Order\Services;

use Domain\Order\Actions\GetDriverOrdersAction;
use Domain\Order\Actions\GetPendingOrdersAction;
use Domain\Order\Actions\MarkOrderAsAssignedAction;
use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Exceptions\OrderNotFoundException;
use Domain\Order\Models\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService implements OrderServiceContract
{
    public function findOrFail(int $id): Order
    {
        return Order::find($id) ?? throw new OrderNotFoundException($id);
    }

    public function getPendingOrders(int $perPage = 15): LengthAwarePaginator
    {
        return (new GetPendingOrdersAction($perPage))->handle();
    }

    public function getDriverOrders(int $driverId, OrderFilterData $filters): LengthAwarePaginator
    {
        return (new GetDriverOrdersAction($driverId, $filters))->handle();
    }

    public function markAsAssigned(int $orderId, int $driverId): Order
    {
        return (new MarkOrderAsAssignedAction($orderId, $driverId))->handle();
    }
}
