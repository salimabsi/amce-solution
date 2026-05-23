<?php

namespace App\Console\Commands\Orders;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:backfill-pending-to-redis')]
#[Description('Mirror every pending order into the Redis ZSET sorted by created_at')]
class BackfillPendingToRedis extends Command
{
    public function handle(PendingOrderStoreContract $store): int
    {
        $count = 0;

        Order::query()
            ->where('status', OrderStatus::Pending)
            ->chunkById(500, function ($orders) use ($store, &$count) {
                foreach ($orders as $order) {
                    $store->add($order->id, $order->created_at->timestamp);
                    $count++;
                }
            });

        $this->info("Backfilled {$count} pending orders to Redis.");

        return self::SUCCESS;
    }
}
