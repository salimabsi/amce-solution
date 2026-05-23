<?php

namespace Domain\Order\Services;

use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Order\Actions\Assignment\Filters\VehicleCapacityFilter;
use Domain\Order\Actions\Assignment\Filters\VehicleTypeFilter;
use Domain\Order\Actions\Assignment\Scorers\DistanceScorer;
use Domain\Order\Actions\Assignment\Scorers\VehicleCapacityFitScorer;
use Domain\Order\Actions\AssignOrderAction;
use Domain\Order\Actions\GetDriverOrdersAction;
use Domain\Order\Actions\GetPendingOrdersAction;
use Domain\Order\Actions\MarkOrderAsAssignedAction;
use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Exceptions\OrderNotFoundException;
use Domain\Order\Models\Entities\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService implements OrderServiceContract
{
    public function __construct(
        private readonly DriverServiceContract $driverService,
        private readonly PendingOrderStoreContract $pendingOrderStore,
    ) {}

    public function findOrFail(int $id): Order
    {
        return Order::find($id) ?? throw new OrderNotFoundException($id);
    }

    public function getPendingOrders(int $perPage = 15): LengthAwarePaginator
    {
        return (new GetPendingOrdersAction($this->pendingOrderStore, $perPage))->handle();
    }

    public function getDriverOrders(int $driverId, OrderFilterData $filters): LengthAwarePaginator
    {
        return (new GetDriverOrdersAction($driverId, $filters))->handle();
    }

    public function markAsAssigned(int $orderId, int $driverId): Order
    {
        return (new MarkOrderAsAssignedAction($orderId, $driverId))->handle();
    }

    public function assignOrder(int $orderId): Order
    {
        return (new AssignOrderAction(
            orderId: $orderId,
            driverService: $this->driverService,
            filters: [
                new VehicleTypeFilter,
                new VehicleCapacityFilter,
            ],
            scorers: [
                new DistanceScorer,
                new VehicleCapacityFitScorer,
            ],
        ))->handle();
    }
}
