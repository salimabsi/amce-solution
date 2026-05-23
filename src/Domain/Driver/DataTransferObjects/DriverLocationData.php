<?php

namespace Domain\Driver\DataTransferObjects;

use Domain\Shared\DataTransferObjects\DataTransferObject;

class DriverLocationData extends DataTransferObject
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {}
}
