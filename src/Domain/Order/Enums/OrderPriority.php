<?php

namespace Domain\Order\Enums;

enum OrderPriority: string
{
    case Normal = 'normal';
    case Vip = 'vip';
}
