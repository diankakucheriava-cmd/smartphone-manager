<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case UAH = 'UAH';

    public static function values(): array
    {
        return array_map(fn($currency) => $currency->value, self::cases());
    }
}
