<?php

namespace Domain\Order\DataTransferObjects;

use Domain\Order\Enums\OrderStatus;
use Domain\Shared\DataTransferObjects\DataTransferObject;

class OrderFilterData extends DataTransferObject
{
    public function __construct(
        public readonly ?OrderStatus $status = null,
        public readonly int $perPage = 15,
    ) {}
}
