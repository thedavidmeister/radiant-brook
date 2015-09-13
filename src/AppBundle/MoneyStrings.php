<?php

namespace AppBundle;

use AppBundle\MoneyConstants;
use Money\Currency;
use Money\Money;

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
        // Inlined for speed.
        if (!is_string($string)) {
            throw new \Exception(json_encode($string) . ' must be a string');
        }

        // USD string may be prepended with a $ char, other symbols should die.
        $string = str_replace('$', '', $string);

        // Inlined for speed.
        if (!is_numeric($string)) {
            throw new \Exception(json_encode($string) . ' must be numeric');
        }

        // Avoid the convenience method as profiling shows it to be too slow
        // here.
        return new Money((int) round($string * (10 ** MoneyConstants::USD_PRECISION)), new Currency('USD'));
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
        // Inlined for speed.
        if (!is_string($string)) {
            throw new \Exception(json_encode($string) . ' must be a string');
        }

        if (!is_numeric($string)) {
            throw new \Exception(json_encode($string) . ' must be numeric');
        }

        // Avoid the convenience method as profiling shows it to be too slow
        // here.
        return new Money((int) round($string * (10 ** MoneyConstants::BTC_PRECISION)), new Currency('BTC'));
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
