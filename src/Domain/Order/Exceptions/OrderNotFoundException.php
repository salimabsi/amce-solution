<?php

namespace Domain\Order\Exceptions;

use RuntimeException;

class OrderNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Order #{$id} not found.");
    }
}
