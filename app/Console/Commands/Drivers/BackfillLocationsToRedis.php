<?php

namespace App\Console\Commands\Drivers;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Models\Entities\DriverLocation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('drivers:backfill-locations-to-redis')]
#[Description('Mirror every driver_locations row into the Redis GEO set')]
class BackfillLocationsToRedis extends Command
{
    public function handle(DriverLocationStoreContract $store): int
    {
        $count = 0;

        DriverLocation::query()->chunkById(500, function ($locations) use ($store, &$count) {
            foreach ($locations as $location) {
                $store->set($location->driver_id, (float) $location->lat, (float) $location->lng);
                $count++;
            }
        });

        $this->info("Backfilled {$count} driver locations to Redis.");

        return self::SUCCESS;
    }
}
