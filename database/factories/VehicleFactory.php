<?php

namespace Database\Factories;

use Domain\Driver\Enums\VehicleType;
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
        $type = fake()->randomElement(VehicleType::cases());

        return [
            'plate_number' => strtoupper(fake()->bothify('??-####-??')),
            'type' => $type,
            'capacity_kg' => match ($type) {
                VehicleType::Motorcycle => fake()->randomFloat(2, 10, 50),
                VehicleType::Car => fake()->randomFloat(2, 100, 400),
                VehicleType::Van => fake()->randomFloat(2, 500, 1500),
                VehicleType::Truck => fake()->randomFloat(2, 2000, 10000),
            },
        ];
    }

    public function motorcycle(): static
    {
        return $this->state(['type' => VehicleType::Motorcycle, 'capacity_kg' => fake()->randomFloat(2, 10, 50)]);
    }

    public function car(): static
    {
        return $this->state(['type' => VehicleType::Car, 'capacity_kg' => fake()->randomFloat(2, 100, 400)]);
    }

    public function van(): static
    {
        return $this->state(['type' => VehicleType::Van, 'capacity_kg' => fake()->randomFloat(2, 500, 1500)]);
    }

    public function truck(): static
    {
        return $this->state(['type' => VehicleType::Truck, 'capacity_kg' => fake()->randomFloat(2, 2000, 10000)]);
    }
}
