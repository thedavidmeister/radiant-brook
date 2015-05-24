<?php

namespace AppBundle;

use Money\Money;

class MoneyStrings
{
    const BTC_PRECISION = 8;

    const USD_PRECISION = 2;

    public static function stringToUSD($string)
    {
        // @see http://stackoverflow.com/questions/14169820/regular-expression-to-match-all-currency-symbols
        $string = preg_replace('@\p{Sc}*@', '', $string);

        if (!is_numeric($string)) {
            throw new \Exception('Could not parse Money::USD from string: ' . $string);
        }
        return Money::USD((int) round($string * (10 ** self::USD_PRECISION)));
    }

    public static function stringToBTC($string)
    {
        if (!is_numeric($string)) {
            throw new \Exception('Could not parse Money::BTC from string: ' . $string);
        }

        return Money::BTC((int) round($string * (10 ** self::BTC_PRECISION)));
    }

    public static function USDToString(Money $USD)
    {
        return (string) number_format(round($USD->getAmount() / 10 ** self::USD_PRECISION, self::USD_PRECISION), self::USD_PRECISION);
    }

    public static function BTCToString(Money $BTC) {
        return (string) number_format(round($BTC->getAmount() / 10 ** self::BTC_PRECISION, self::BTC_PRECISION), self::BTC_PRECISION);
    }
}
