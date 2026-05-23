<?php

namespace Domain\Driver\Contracts;

interface DriverLocationStoreContract
{
    public function set(int $driverId, float $lat, float $lng): void;

    public function remove(int $driverId): void;

    /** @return int[] */
    public function findNearbyIds(float $lat, float $lng, float $radiusKm): array;
}
