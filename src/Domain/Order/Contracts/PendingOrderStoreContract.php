<?php

namespace Domain\Order\Contracts;

interface PendingOrderStoreContract
{
    public function add(int $orderId, int $timestamp): void;

    public function remove(int $orderId): void;

    /** @return int[] */
    public function paginateIds(int $page, int $perPage): array;

    public function count(): int;
}
