<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyFromString;
use Money\Money;

/**
 * Wraps a list of orders provided by Bitstamp to handle some basic statistics.
 *
 * All methods must either return a single pair, a list of pairs or an aggregate
 * value.
 */
class OrderList
{
    protected $data;

    const USD_PRICE_DATUM_INDEX = 0;
    const USD_KEY = 'usd';

    const BTC_AMOUNT_DATUM_INDEX = 1;
    const BTC_KEY = 'btc';

    /**
     * Constructor.
     *
     * @param array $data
     *   Order list data from Bitstamp. Either the 'bids' or 'asks' array from
     *   a full order book array.
     */
    public function __construct($data)
    {
        $this->data = [];
        foreach ($data as $datum) {
            $this->data[] = [
                self::USD_KEY => MoneyFromString::USD($datum[self::USD_PRICE_DATUM_INDEX]),
                self::BTC_KEY => MoneyFromString::BTC($datum[self::BTC_AMOUNT_DATUM_INDEX]),
            ];
        }
    }

    /**
     * Utility.
     */

    /**
     * Sorts set by USD asc.
     */
    protected function sortUSDAsc()
    {
        usort($this->data, function($a, $b) {
            if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
                return 0;
            }

            return $a[self::USD_KEY] < $b[self::USD_KEY] ? -1 : 1;
        });
    }

    /**
     * Sorts set by USD desc.
     */
    protected function sortUSDDesc()
    {
        usort($this->data, function($a, $b) {
            if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
                return 0;
            }

            return $a[self::USD_KEY] > $b[self::USD_KEY] ? -1 : 1;
        });
    }

    /**
     * API.
     */

    /**
     * Individual Money pairs as ['usd' => Money::USD, 'btc' => Money::BTC].
     */

    /**
     * Returns the minimum USD value of the order list.
     *
     * @return array
     *   A Money pair array representing the minimum USD order.
     */
    public function min()
    {
        $this->sortUSDAsc();

        return reset($this->data);
    }

    /**
     * Returns the maximum USD value of the order list.
     *
     * @return array
     *   A Money pair array representing the maximum USD order.
     */
    public function max()
    {
        $this->sortUSDDesc();

        return reset($this->data);
    }

    /**
     * Lists of Money pairs.
     */

    /**
     * Expose the data without allowing modification.
     *
     * It is not a good idea to call this outside unit tests.
     *
     * @return array
     *   A list of Money pairs.
     */
    // public function data()
    // {
    //     return $this->data;
    // }

    /**
     * Aggregate functions.
     */

    /**
     * Calculates the total BTC Volume of the order list.
     *
     * @return int
     *   An aggregate value representing the BTC total volume of the order list
     *   in Satoshis.
     */
    public function totalVolume()
    {
        $sum = Money::BTC(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::BTC_KEY]);
        }

        return $sum->getAmount();
    }

    /**
     * Calculates total capitalisation of the order list.
     *
     * @return int
     *   An aggregate value representing the market cap in USDcentsSatoshis.
     */
    public function totalCap()
    {
        $sum = Money::USD(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
        }

        return $sum->getAmount();
    }

    /**
     * Calculate a percentile of the BTC Volume using the "Nearest rank" method.
     *
     * To find Holly's grade, we need to do the following steps:
     *
     *   1. Multiply the total number of values in the data set by the
     *      percentile, which will give you the index.
     *   2. Order all of the values in the data set in ascending order (least to
     *      greatest).
     *   3. If the index is a whole number, count the values in the data set
     *      from least to greatest until you reach the index, then take the
     *      index and the next greatest number and find the average.
     *   4. If the index is not a whole number, round the number up, then count
     *      the values in the data set from least to greatest, until you reach
     *      the index.
     *
     * In this analogy, each satoshi on the market is a "student" and the USD
     * price is a "grade".
     *
     * @see http://study.com/academy/lesson/finding-percentiles-in-a-data-set-formula-examples-quiz.html
     * @see http://en.wikipedia.org/wiki/Percentile
     *
     * @param float $pc
     *   Float between 0 - 1 represending the percentile.
     *
     * @return int
     *   An aggregate value representing the USD price of the percentile
     *   calculated against BTC Volume, in USD cents.
     */
    public function percentileBTCVolume($pc)
    {
        if ($pc < 0 || $pc > 1) {
            throw new \Exception('Percentage must be between 0 - 1.');
        }
        // 1. Calculate the index, rounding up any decimals, which is not how
        // Money would normally work if we called multiply().
        $index = Money::BTC((int) ceil($this->totalVolume() * $pc));

        // 2. Order all the values in the set in ascending order.
        $this->sortUSDAsc();

        // If index is less than the running total of the next datum, return the
        // current datum.
        $sum = Money::BTC(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::BTC_KEY]);
            if ($index <= $sum) {
                return $datum[self::USD_KEY]->getAmount();
            }
        }
    }

    /**
     * Calculates a given percentile based off order list capitalisation.
     *
     * @see percentileBTCVolume()
     *
     * @param float $pc
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return int
     *   An aggregate value representing the USD price of the percentile
     *   calculated against market cap, in USD cents.
     */
    public function percentileCap($pc)
    {
        if ($pc < 0 || $pc > 1) {
            throw new \Exception('Percentage must be between 0 - 1.');
        }

        $index = Money::USD((int) ceil($this->totalCap() * $pc));
        $this->sortUSDAsc();

        $sum = Money::USD(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
            if ($index <= $sum) {
                return $datum[self::USD_KEY]->getAmount();
            }
        }
    }

}
