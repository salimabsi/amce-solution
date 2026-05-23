<?php

namespace Domain\Driver\Exceptions;

use RuntimeException;

class DriverNotFoundException extends RuntimeException
{
    public function __construct(int $id)
    {
        parent::__construct("Driver #{$id} not found.");
    }
}
