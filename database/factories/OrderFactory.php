<?php

namespace Database\Factories;

use Domain\Driver\Models\Entities\Driver;
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
            'status' => 'pending',
            'type' => fake()->randomElement(['standard', 'fragile', 'refrigerated', 'hazardous']),
            'priority' => fake()->randomElement(['normal', 'vip']),
            'weight_kg' => fake()->randomFloat(2, 1, 500),
            'pickup_lat' => fake()->latitude(24.0, 25.5),
            'pickup_lng' => fake()->longitude(46.0, 47.5),
            'dropoff_lat' => fake()->latitude(24.0, 25.5),
            'dropoff_lng' => fake()->longitude(46.0, 47.5),
            'driver_id' => null,
            'assigned_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending', 'driver_id' => null, 'assigned_at' => null]);
    }

    public function assigned(): static
    {
        return $this->state(fn () => [
            'status' => 'assigned',
            'driver_id' => Driver::factory(),
            'assigned_at' => now(),
        ]);
    }

    public function beingServed(): static
    {
        return $this->state(fn () => [
            'status' => 'being_served',
            'driver_id' => Driver::factory(),
            'assigned_at' => now()->subMinutes(fake()->numberBetween(5, 60)),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'driver_id' => Driver::factory(),
            'assigned_at' => now()->subHours(fake()->numberBetween(1, 48)),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }

    public function vip(): static
    {
        return $this->state(['priority' => 'vip']);
    }
}
