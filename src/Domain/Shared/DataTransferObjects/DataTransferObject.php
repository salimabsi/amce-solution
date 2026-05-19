<?php

namespace Domain\Shared\DataTransferObjects;

abstract class DataTransferObject
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
