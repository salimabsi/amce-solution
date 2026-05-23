<?php

namespace Presentation\Api\Controllers;

use Domain\Driver\Actions\GetAvailableDriversNearbyHaversineAction;
use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Order\Contracts\OrderServiceContract;
use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Presentation\Api\Requests\DriverOrdersRequest;
use Presentation\Api\Resources\DriverResource;
use Presentation\Api\Resources\OrderResource;

class DriverController
{
    public function __construct(
        private readonly DriverServiceContract $driverService,
        private readonly OrderServiceContract $orderService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return DriverResource::collection($this->driverService->getDrivers());
    }

    // BENCHMARK-ONLY: returns nearby drivers via Redis GEO. See benchmarks/SUMMARY.md.
    public function nearby(Request $request): JsonResponse
    {
        $drivers = $this->driverService->getAvailableDriversNearby(
            (float) $request->query('lat'),
            (float) $request->query('lng'),
            (float) $request->query('radius', '5'),
        );

        return response()->json(['count' => $drivers->count(), 'ids' => $drivers->pluck('id')]);
    }

    // BENCHMARK-ONLY: same shape via the original PHP-Haversine path.
    public function nearbyHaversine(Request $request): JsonResponse
    {
        $drivers = (new GetAvailableDriversNearbyHaversineAction(
            (float) $request->query('lat'),
            (float) $request->query('lng'),
            (float) $request->query('radius', '5'),
        ))->handle();

        return response()->json(['count' => $drivers->count(), 'ids' => $drivers->pluck('id')]);
    }

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
