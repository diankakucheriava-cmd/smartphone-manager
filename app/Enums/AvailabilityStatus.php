<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case InStock    = 'In Stock';
    case OutOfStock = 'Out of Stock';
    case LowStock   = 'Low Stock';
}
