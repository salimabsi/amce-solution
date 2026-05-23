<?php

namespace Presentation\Api\Controllers;

use Domain\Order\Contracts\OrderServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Presentation\Api\Resources\OrderResource;

class OrderController
{
    public function __construct(private readonly OrderServiceContract $orderService) {}

    public function pending(): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderService->getPendingOrders());
    }

    public function assign(int $id): JsonResponse
    {
        $order = $this->orderService->assignOrder($id);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(200);
    }
}
