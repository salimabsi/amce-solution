<?php

namespace Domain\Order\Actions;

use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\Actions\Action;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * BENCHMARK-ONLY: the original DB-direct read path with the partial index.
 * Kept for A/B comparison against GetPendingOrdersAction (Redis ZSET).
 * See benchmarks/SUMMARY.md.
 */
class GetPendingOrdersFromDbAction extends Action
{
    public function __construct(private readonly int $perPage = 15) {}

    public function handle(): LengthAwarePaginator
    {
        return Order::where('status', OrderStatus::Pending)
            ->orderBy('created_at')
            ->paginate($this->perPage);
    }
}
