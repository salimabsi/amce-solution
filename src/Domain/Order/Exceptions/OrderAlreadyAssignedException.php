<?php

namespace Domain\Order\Exceptions;

use RuntimeException;

class OrderAlreadyAssignedException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Order #{$id} is no longer available for assignment.");
    }
}
