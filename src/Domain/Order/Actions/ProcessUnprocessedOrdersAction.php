<?php

namespace Domain\Order\Actions;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Enums\OrderStatus;
use Domain\Order\Models\Entities\UnprocessedOrder;
use Domain\Shared\Actions\Action;
use Illuminate\Support\Facades\DB;

class ProcessUnprocessedOrdersAction extends Action
{
    public function __construct(
        private readonly PendingOrderStoreContract $store,
        private readonly int $batchSize = 500,
    ) {}

    public function handle(): int
    {
        return DB::transaction(function () {
            $batch = UnprocessedOrder::query()
                ->orderBy('id')
                ->limit($this->batchSize)
                ->lockForUpdate()
                ->get();

            if ($batch->isEmpty()) {
                return 0;
            }

            $now = now();
            $rows = $batch->map(fn (UnprocessedOrder $u) => array_merge($u->payload, [
                'status' => OrderStatus::Pending->value,
                'created_at' => $u->created_at,
                'updated_at' => $now,
            ]))->all();

            $inserted = $this->bulkInsertReturningIds('orders', $rows);

            foreach ($inserted as $i => $row) {
                $this->store->add((int) $row->id, $batch[$i]->created_at->timestamp);
            }

            UnprocessedOrder::whereIn('id', $batch->pluck('id'))->delete();

            return count($inserted);
        });
    }

    /**
     * Bulk INSERT ... RETURNING id, created_at — PostgreSQL preserves input order.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, object{id:int, created_at:string}>
     */
    private function bulkInsertReturningIds(string $table, array $rows): array
    {
        $columns = array_keys($rows[0]);
        $rowPlaceholder = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $valuesSql = implode(', ', array_fill(0, count($rows), $rowPlaceholder));

        $bindings = [];
        foreach ($rows as $row) {
            foreach ($columns as $col) {
                $bindings[] = $row[$col];
            }
        }

        $sql = 'INSERT INTO '.$table.' ('.implode(', ', $columns).') VALUES '.$valuesSql.' RETURNING id, created_at';

        return DB::select($sql, $bindings);
    }
}
