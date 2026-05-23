<?php

namespace Domain\Driver\Services;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Illuminate\Support\Facades\Redis;

class RedisDriverLocationStore implements DriverLocationStoreContract
{
    private const KEY = 'drivers:locations';

    public function set(int $driverId, float $lat, float $lng): void
    {
        Redis::geoadd(self::KEY, $lng, $lat, (string) $driverId);
    }

    public function remove(int $driverId): void
    {
        Redis::zrem(self::KEY, (string) $driverId);
    }

    /** @return int[] */
    public function findNearbyIds(float $lat, float $lng, float $radiusKm): array
    {
        $members = Redis::geosearch(self::KEY, [$lng, $lat], $radiusKm, 'km');

        return array_map('intval', $members ?: []);
    }
}
