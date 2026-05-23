<?php

namespace Database\Factories;

use Domain\Driver\Models\Entities\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $type = fake()->randomElement(['motorcycle', 'car', 'van', 'truck']);

        return [
            'plate_number' => strtoupper(fake()->bothify('??-####-??')),
            'type' => $type,
            'capacity_kg' => match ($type) {
                'motorcycle' => fake()->randomFloat(2, 10, 50),
                'car' => fake()->randomFloat(2, 100, 400),
                'van' => fake()->randomFloat(2, 500, 1500),
                'truck' => fake()->randomFloat(2, 2000, 10000),
            },
        ];
    }

    public function motorcycle(): static
    {
        return $this->state(['type' => 'motorcycle', 'capacity_kg' => fake()->randomFloat(2, 10, 50)]);
    }

    public function car(): static
    {
        return $this->state(['type' => 'car', 'capacity_kg' => fake()->randomFloat(2, 100, 400)]);
    }

    public function van(): static
    {
        return $this->state(['type' => 'van', 'capacity_kg' => fake()->randomFloat(2, 500, 1500)]);
    }

    public function truck(): static
    {
        return $this->state(['type' => 'truck', 'capacity_kg' => fake()->randomFloat(2, 2000, 10000)]);
    }
}
