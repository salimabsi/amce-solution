<?php

namespace Database\Seeders;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Heavy-traffic seeder used for read-path benchmarks.
 *
 *   php artisan db:seed --class=BenchmarkSeeder
 *
 * Wipes the orders table and the pending ZSET, then bulk-inserts ~1M orders:
 *   50,000 pending  + 950,000 historical (completed/cancelled).
 */
class BenchmarkSeeder extends Seeder
{
    private const TOTAL = 1_000_000;

    private const PENDING = 50_000;

    private const CHUNK = 5_000;

    public function run(): void
    {
        $this->command->info('Wiping orders table and Redis ZSET...');
        DB::table('orders')->truncate();
        Redis::del('orders:pending');

        $this->command->info('Bulk-inserting '.number_format(self::TOTAL).' orders...');
        $bar = $this->command->getOutput()->createProgressBar(self::TOTAL / self::CHUNK);
        $baseTime = now()->subSeconds(self::TOTAL);

        for ($batch = 0; $batch < self::TOTAL / self::CHUNK; $batch++) {
            $rows = [];
            for ($i = 0; $i < self::CHUNK; $i++) {
                $idx = $batch * self::CHUNK + $i;
                $status = $idx < self::PENDING
                    ? OrderStatus::Pending->value
                    : ($idx % 2 === 0 ? OrderStatus::Completed->value : OrderStatus::Cancelled->value);

                $rows[] = [
                    'status' => $status,
                    'type' => 'standard',
                    'priority' => 'normal',
                    'weight_kg' => 50,
                    'pickup_lat' => 24.7,
                    'pickup_lng' => 46.7,
                    'dropoff_lat' => 24.75,
                    'dropoff_lng' => 46.75,
                    'created_at' => $baseTime->copy()->addSeconds($idx),
                    'updated_at' => $baseTime,
                ];
            }
            DB::table('orders')->insert($rows);
            $bar->advance();
        }
        $bar->finish();
        $this->command->newLine();

        $this->command->info('Pushing '.number_format(self::PENDING).' pending orders into Redis ZSET...');
        $store = app(PendingOrderStoreContract::class);
        Order::query()
            ->where('status', OrderStatus::Pending)
            ->select('id', 'created_at')
            ->chunkById(5000, function ($orders) use ($store) {
                foreach ($orders as $order) {
                    $store->add($order->id, $order->created_at->timestamp);
                }
            });

        $this->command->info('Done. '.number_format(self::PENDING).' pending / '.number_format(self::TOTAL).' total.');
    }
}
