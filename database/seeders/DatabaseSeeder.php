<?php

namespace Database\Seeders;

use App\Models\User;
use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Available drivers with vehicles and locations
        $availableDrivers = Driver::factory(8)
            ->withLocation()
            ->create();

        // Unavailable drivers (already serving)
        Driver::factory(3)
            ->unavailable()
            ->withLocation()
            ->create();

        // Pending orders — visible on the operations team page
        Order::factory(12)->pending()->create();

        // Orders assigned to available drivers
        Order::factory(6)
            ->assigned()
            ->recycle($availableDrivers)
            ->create();

        // Orders in service
        Order::factory(5)
            ->beingServed()
            ->recycle($availableDrivers)
            ->create();

        // Historical orders
        Order::factory(10)->completed()->recycle($availableDrivers)->create();
        Order::factory(3)->cancelled()->create();
    }
}
