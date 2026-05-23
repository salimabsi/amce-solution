<?php

namespace Database\Factories;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Enums\OrderPriority;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Enums\OrderType;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'status' => OrderStatus::Pending,
            'type' => fake()->randomElement(OrderType::cases()),
            'priority' => fake()->randomElement(OrderPriority::cases()),
            'weight_kg' => fake()->randomFloat(2, 1, 500),
            'pickup_lat' => fake()->latitude(24.55, 24.85),
            'pickup_lng' => fake()->longitude(46.55, 46.85),
            'dropoff_lat' => fake()->latitude(24.55, 24.85),
            'dropoff_lng' => fake()->longitude(46.55, 46.85),
            'driver_id' => null,
            'assigned_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => OrderStatus::Pending, 'driver_id' => null, 'assigned_at' => null]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Assigned,
            'driver_id' => Driver::factory(),
            'assigned_at' => now(),
        ]);
    }

    public function beingServed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::BeingServed,
            'driver_id' => Driver::factory(),
            'assigned_at' => now()->subMinutes(fake()->numberBetween(5, 60)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Completed,
            'driver_id' => Driver::factory(),
            'assigned_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderStatus::Cancelled]);
    }

    public function vip(): static
    {
        return $this->state(['priority' => OrderPriority::Vip]);
    }
}
