<?php

namespace Domain\Order\Contracts;

use Domain\Order\Models\Entities\Order;
use Illuminate\Database\Eloquent\Collection;

interface DriverFilterContract
{
    public function filter(Collection $drivers, Order $order): Collection;
}
