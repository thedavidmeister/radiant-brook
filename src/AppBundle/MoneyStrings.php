<?php

namespace AppBundle;

use Money\Money;

/**
 * Converts strings to Money and vice-versa.
 */
class MoneyStrings
{
    const BTC_PRECISION = 8;

    const USD_PRECISION = 2;

    /**
     * Converts a string in USD (not cents) XXXX.YY to Money::USD
     *
     * @param string $string
     *   The string to convert.
     *
     * @return Money::USD
     */
    public static function stringToUSD($string)
    {
        if (!is_string($string)) {
            throw new \Exception('The parameter passed to stringToUSD must be a string');
        }

        // @see http://stackoverflow.com/questions/14169820/regular-expression-to-match-all-currency-symbols
        $string = preg_replace('@\p{Sc}*@', '', $string);

        if (!is_numeric($string)) {
            throw new \Exception('Could not parse Money::USD from string: ' . $string);
        }
        return Money::USD((int) round($string * (10 ** self::USD_PRECISION)));
    }

    /**
     * Converts a string in BTC (NOT satoshis) XXXX.YYYYYYYY to Money::BTC
     *
     * @param  string $string
     *   The string to convert.
     *
     * @return Money::BTC
     */
    public static function stringToBTC($string)
    {
        if (!is_string($string)) {
            throw new \Exception('The parameter passed to stringToBTC must be a string');
        }

        if (!is_numeric($string)) {
            throw new \Exception('Could not parse Money::BTC from string: ' . $string);
        }

        return Money::BTC((int) round($string * (10 ** self::BTC_PRECISION)));
    }

    /**
     * Converts Money::USD to a string in XXXX.YY format.
     *
     * @param Money::USD $USD
     */
    public static function USDToString(Money $USD)
    {
        return (string) number_format(round($USD->getAmount() / 10 ** self::USD_PRECISION, self::USD_PRECISION), self::USD_PRECISION);
    }

    /**
     * Converts Money::BTC to a string in XXXX.YYYYYYYY format.
     *
     * @param Money::BTC $BTC
     */
    public static function BTCToString(Money $BTC)
    {
        return (string) number_format(round($BTC->getAmount() / 10 ** self::BTC_PRECISION, self::BTC_PRECISION), self::BTC_PRECISION);
    }
}
