<?php

namespace Domain\Order\Actions\Assignment\Filters;

use Domain\Driver\Enums\VehicleType;
use Domain\Order\Contracts\DriverFilterContract;
use Domain\Order\Enums\OrderType;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Collection;

class VehicleTypeFilter implements DriverFilterContract
{
    /** @var array<string, VehicleType[]> */
    private const COMPATIBLE_TYPES = [
        OrderType::Standard->value => [VehicleType::Motorcycle, VehicleType::Car, VehicleType::Van, VehicleType::Truck],
        OrderType::Fragile->value => [VehicleType::Car, VehicleType::Van, VehicleType::Truck],
        OrderType::Refrigerated->value => [VehicleType::Van, VehicleType::Truck],
        OrderType::Hazardous->value => [VehicleType::Truck],
    ];

    public function filter(Collection $drivers, Order $order): Collection
    {
        $compatible = self::COMPATIBLE_TYPES[$order->type->value];

        return $drivers->filter(
            fn ($driver) => $driver->vehicle && in_array($driver->vehicle->type, $compatible)
        )->values();
    }
}
