<?php

namespace AppBundle;

use Money\Money;

class MoneyStrings
{
    const BTC_PRECISION = 8;

    const USD_PRECISION = 2;

    public static function stringToUSD($string)
    {
        return Money::USD((int) round($string * (10 ** self::USD_PRECISION)));
    }

    public static function stringToBTC($string)
    {
        return Money::BTC((int) round($string * (10 ** self::BTC_PRECISION)));
    }

    public static function USDToString(Money $USD)
    {
        return (string) round($USD->getAmount() / 10 ** self::USD_PRECISION, self::USD_PRECISION);
    }

    public static function BTCToString(Money $BTC) {
        return (string) round($BTC->getAmount() / 10 ** self::BTC_PRECISION, self::BTC_PRECISION);
    }
}
