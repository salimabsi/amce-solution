<?php

namespace Domain\Order\Actions;

use Domain\Order\DataTransferObjects\OrderFilterData;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\Actions\Action;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetDriverOrdersAction extends Action
{
    public function __construct(
        private readonly int $driverId,
        private readonly OrderFilterData $filters,
    ) {}

    public function handle(): LengthAwarePaginator
    {
        return Order::where('driver_id', $this->driverId)
            ->when($this->filters->status, fn ($query) => $query->where('status', $this->filters->status))
            ->orderBy('created_at', 'desc')
            ->paginate($this->filters->perPage);
    }
}
