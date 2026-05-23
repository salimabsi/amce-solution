<?php

namespace Domain\Order\Enums;

enum OrderType: string
{
    case Standard = 'standard';
    case Fragile = 'fragile';
    case Refrigerated = 'refrigerated';
    case Hazardous = 'hazardous';
}
