<?php

namespace Domain\Order\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case BeingServed = 'being_served';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
