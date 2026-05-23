<?php

namespace Domain\Order\Exceptions;

use RuntimeException;

class NoAvailableDriverException extends RuntimeException
{
    public function __construct(int $orderId)
    {
        parent::__construct("No available driver found for order #{$orderId}.");
    }
}
