<?php

namespace Domain\Order\Actions;

use Domain\Order\Contracts\PendingOrderStoreContract;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\Actions\Action;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Pagination\Paginator as PaginatorFacade;

class GetPendingOrdersAction extends Action
{
    public function __construct(
        private readonly PendingOrderStoreContract $store,
        private readonly int $perPage = 15,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        $page = PaginatorFacade::resolveCurrentPage();
        $total = $this->store->count();
        $ids = $this->store->paginateIds($page, $this->perPage);

        $orders = empty($ids)
            ? collect()
            : Order::whereIn('id', $ids)->get()->sortBy(fn ($o) => array_search($o->id, $ids))->values();

        return new Paginator($orders, $total, $this->perPage, $page, [
            'path' => PaginatorFacade::resolveCurrentPath(),
        ]);
    }
}
