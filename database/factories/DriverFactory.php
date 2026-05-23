<?php

namespace Database\Factories;

use App\Models\User;
use Domain\Driver\Models\Entities\Driver;
use Domain\Driver\Models\Entities\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'is_available' => true,
            'vehicle_id' => Vehicle::factory(),
        ];
    }

    public function unavailable(): static
    {
        return $this->state(['is_available' => false]);
    }

    public function withLocation(): static
    {
        return $this->afterCreating(function (Driver $driver) {
            $driver->location()->create([
                'lat' => fake()->latitude(24.55, 24.85),
                'lng' => fake()->longitude(46.55, 46.85),
            ]);
        });
    }
}
