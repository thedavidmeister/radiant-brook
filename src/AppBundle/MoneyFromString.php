<?php

namespace AppBundle;

use Money\Money;

class MoneyFromString
{
    const BTC_PRECISION = 8;

    const USD_PRECISION = 2;

    public static function USD($string)
    {
        return Money::USD((int) round($string * (10 ** self::USD_PRECISION)));
    }

    public static function BTC($string)
    {
        return Money::BTC((int) round($string * (10 ** self::BTC_PRECISION)));
    }
}
