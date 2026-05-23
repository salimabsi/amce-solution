<?php

namespace App\Console\Commands\Orders;

use Domain\Order\Contracts\OrderServiceContract;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:process {--batch=500 : Max rows to drain in one run} {--loop : Keep draining until the staging table is empty}')]
#[Description('Drain unprocessed_orders into orders + Redis pending ZSET')]
class ProcessUnprocessedOrders extends Command
{
    public function handle(OrderServiceContract $orders): int
    {
        $batchSize = (int) $this->option('batch');
        $loop = (bool) $this->option('loop');
        $total = 0;

        do {
            $processed = $orders->processUnprocessedBatch($batchSize);
            $total += $processed;
            if ($processed > 0) {
                $this->line("Processed {$processed} orders");
            }
        } while ($loop && $processed > 0);

        $this->info("Done. Total processed: {$total}");

        return self::SUCCESS;
    }
}
