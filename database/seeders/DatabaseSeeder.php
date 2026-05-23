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

        // Available drivers — no active orders
        $availableDrivers = Driver::factory(8)
            ->withLocation()
            ->create();

        // One driver per assigned order — each is unavailable
        $assignedDrivers = Driver::factory(16)
            ->unavailable()
            ->withLocation()
            ->create();

        // One driver per being-served order — each is unavailable
        $beingServedDrivers = Driver::factory(15)
            ->unavailable()
            ->withLocation()
            ->create();

        // Pending orders — visible on the operations team page
        Order::factory(22)->pending()->create();

        // One assigned order per driver (enforced by DB unique partial index)
        Order::factory(16)
            ->assigned()
            ->sequence(fn ($seq) => ['driver_id' => $assignedDrivers[$seq->index]->id])
            ->create();

        // One being-served order per driver
        Order::factory(15)
            ->beingServed()
            ->sequence(fn ($seq) => ['driver_id' => $beingServedDrivers[$seq->index]->id])
            ->create();

        // Historical orders — can share drivers freely
        Order::factory(20)->completed()->recycle($availableDrivers)->create();
        Order::factory(13)->cancelled()->create();
    }
}
