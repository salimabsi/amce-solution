<?php

namespace Domain\Order\Contracts;

use Domain\Driver\Models\Entities\Driver;
use Domain\Order\Models\Entities\Order;

interface DriverScorerContract
{
    public function score(Driver $driver, Order $order): float;
}
