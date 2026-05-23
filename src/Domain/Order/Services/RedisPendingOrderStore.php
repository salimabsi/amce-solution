<?php

namespace Domain\Order\Services;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Illuminate\Support\Facades\Redis;

class RedisPendingOrderStore implements PendingOrderStoreContract
{
    private const KEY = 'orders:pending';

    public function add(int $orderId, int $timestamp): void
    {
        Redis::zadd(self::KEY, $timestamp, (string) $orderId);
    }

    public function remove(int $orderId): void
    {
        Redis::zrem(self::KEY, (string) $orderId);
    }

    /** @return int[] */
    public function paginateIds(int $page, int $perPage): array
    {
        $start = ($page - 1) * $perPage;
        $stop = $start + $perPage - 1;

        $ids = Redis::zrange(self::KEY, $start, $stop);

        return array_map('intval', $ids ?: []);
    }

    public function count(): int
    {
        return (int) Redis::zcard(self::KEY);
    }
}
