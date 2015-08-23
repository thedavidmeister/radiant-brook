<?php

namespace AppBundle;

use Money\Money;
use AppBundle\Ensure;
use AppBundle\MoneyConstants;

/**
 * Converts strings to Money and vice-versa.
 */
class MoneyStrings
{
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
        Ensure::isString($string);

        // @see http://stackoverflow.com/questions/14169820/regular-expression-to-match-all-currency-symbols
        $string = preg_replace('@\p{Sc}*@', '', $string);

        Ensure::isNumeric($string);

        return Money::USD((int) round($string * (10 ** MoneyConstants::USD_PRECISION)));
    }

    /**
     * Converts a string in BTC (NOT satoshis) XXXX.YYYYYYYY to Money::BTC
     *
     * @param string $string
     *   The string to convert.
     *
     * @return Money::BTC
     */
    public static function stringToBTC($string)
    {
        Ensure::isString($string);
        Ensure::isNumeric($string);

        return Money::BTC((int) round($string * (10 ** MoneyConstants::BTC_PRECISION)));
    }

    /**
     * Converts Money::USD to a string in XXXX.YY format.
     *
     * @param Money::USD $USD
     *
     * @return string
     *   USD string in XXXX.YY format.
     */
    public static function USDToString(Money $USD)
    {
        return (string) number_format(round($USD->getAmount() / 10 ** MoneyConstants::USD_PRECISION, MoneyConstants::USD_PRECISION), MoneyConstants::USD_PRECISION);
    }

    /**
     * Converts Money::BTC to a string in XXXX.YYYYYYYY format.
     *
     * @param Money::BTC $BTC
     *
     * @return string
     *   BTC string in XXXX.YYYYYYYY format.
     */
    public static function BTCToString(Money $BTC)
    {
        return (string) number_format(round($BTC->getAmount() / 10 ** MoneyConstants::BTC_PRECISION, MoneyConstants::BTC_PRECISION), MoneyConstants::BTC_PRECISION);
    }
}
