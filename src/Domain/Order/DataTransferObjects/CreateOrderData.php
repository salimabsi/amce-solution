<?php

namespace Domain\Order\DataTransferObjects;

use Domain\Order\Enums\OrderPriority;
use Domain\Order\Enums\OrderType;

readonly class CreateOrderData
{
    public function __construct(
        public OrderType $type,
        public OrderPriority $priority,
        public float $weightKg,
        public float $pickupLat,
        public float $pickupLng,
        public float $dropoffLat,
        public float $dropoffLng,
    ) {}

    /** @return array<string, mixed> */
    public function toOrderRow(): array
    {
        return [
            'type' => $this->type->value,
            'priority' => $this->priority->value,
            'weight_kg' => $this->weightKg,
            'pickup_lat' => $this->pickupLat,
            'pickup_lng' => $this->pickupLng,
            'dropoff_lat' => $this->dropoffLat,
            'dropoff_lng' => $this->dropoffLng,
        ];
    }
}
