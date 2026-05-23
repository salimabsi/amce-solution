<?php

namespace Presentation\Api\Controllers;

use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Presentation\Api\Requests\DriverOrdersRequest;
use Presentation\Api\Resources\OrderResource;

class DriverController
{
    public function __construct(
        private readonly DriverServiceContract $driverService,
        private readonly OrderServiceContract $orderService,
    ) {}

    public function orders(DriverOrdersRequest $request, int $id): AnonymousResourceCollection|JsonResponse
    {
        $this->driverService->findOrFail($id);

        $filters = new OrderFilterData(
            status: $request->validated('status') ? OrderStatus::from($request->validated('status')) : null,
            perPage: (int) $request->validated('per_page', 15),
        );

        return OrderResource::collection(
            $this->orderService->getDriverOrders($id, $filters)
        );
    }
}
