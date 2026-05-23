<?php

namespace Domain\Order\Actions;

use Domain\Order\DataTransferObjects\CreateOrderData;
use Domain\Order\Models\Entities\UnprocessedOrder;
use Domain\Shared\Actions\Action;

class QueueOrderForProcessingAction extends Action
{
    public function __construct(private readonly CreateOrderData $data) {}

    public function handle(): UnprocessedOrder
    {
        return UnprocessedOrder::create([
            'payload' => $this->data->toOrderRow(),
        ]);
    }
}
