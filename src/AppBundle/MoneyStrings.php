<?php

namespace AppBundle;

use Money\Money;
use Money\Currency;
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

        // USD string may be prepended with a $ char, other symbols should die.
        $string = str_replace('$', '', $string);

        Ensure::isNumeric($string);

        // Profiling shows this is ~40% faster than the convenience method.
        static $usd;
        if (!isset($usd)) {
            $usd = new Currency('USD');
        }

        return new Money((int) round($string * (10 ** MoneyConstants::USD_PRECISION)), $usd);
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

        static $btc;
        if (!isset($btc)) {
            $btc = new Currency('BTC');
        }

        return new Money((int) round($string * (10 ** MoneyConstants::BTC_PRECISION)), $btc);
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
