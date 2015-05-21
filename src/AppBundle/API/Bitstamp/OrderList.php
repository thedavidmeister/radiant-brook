<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyFromString;
use Money\Money;

/**
 * Wraps a list of orders provided by Bitstamp to handle some basic statistics.
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
     * Expose the data without allowing modification.
     *
     * @return array
     *   The internal data array.
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Utility function for sorting ascending.
     *
     * @todo Test me.
     */
    protected function sortAsc()
    {
        usort($this->data, function($a, $b) {
            if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
                return 0;
            }

            return $a[self::USD_KEY] < $b[self::USD_KEY] ? -1 : 1;
        });
    }

    /**
     * Utility function for sorting descending.
     */
    protected function sortDesc()
    {
        usort($this->data, function($a, $b) {
            if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
                return 0;
            }

            return $a[self::USD_KEY] > $b[self::USD_KEY] ? -1 : 1;
        });
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
     * @return float
     *   The price at the given percentile.
     */
    public function percentileBTCVolume($pc)
    {
        if ($pc < 0 || $pc > 1) {
            throw new \Exception('Percentage must be between 0 - 1.');
        }
        // 1. Calculate the index, rounding up any decimals, which is not how
        // Money would normally work if we called multiply().
        $index = Money::BTC((int) ceil($this->totalVolume()->getAmount() * $pc));

        // 2. Order all the values in the set in ascending order.
        $this->sortAsc();

        // If index is less than the running total of the next datum, return the
        // current datum.
        $sum = Money::BTC(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::BTC_KEY]);
            if ($index <= $sum) {
                return $datum[self::USD_KEY];
            }
        }
    }

    /**
     * Calculates the total BTC Volume of the order list.
     *
     * @return Money::BTC
     *   The total BTC Volume of the order list.
     */
    public function totalVolume()
    {
        $sum = Money::BTC(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::BTC_KEY]);
        }

        return $sum;
    }

    /**
     * Returns the minimum value of the order list.
     *
     * @return array
     *   The minimum order.
     */
    public function min()
    {
        $this->sortAsc();

        return reset($this->data);
    }

    /**
     * Returns the maximum value of the order list.
     *
     * @return array
     *   The maximum order.
     */
    public function max()
    {
        $this->sortDesc();

        return reset($this->data);
    }

    /**
     * Calculates total capitalisation of the order list.
     *
     * @return float
     *   The total capitalisation of the order list.
     */
    public function totalCap()
    {
        $sum = Money::USD(0);
        foreach ($this->data as $datum) {
          $sum = $sum->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
        }

        return $sum;
    }

    /**
     * Calculates a given percentile based off order list capitalisation.
     *
     * @see percentileBTCVolume()
     *
     * @param float $pc
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return array
     *   The order representing the requested percentile.
     */
    public function percentileCap($pc)
    {
        if ($pc < 0 || $pc > 1) {
            throw new \Exception('Percentage must be between 0 - 1.');
        }

        $index = Money::USD((int) ceil($this->totalCap()->getAmount() * $pc));
        $this->sortAsc();

        $sum = Money::USD(0);
        foreach ($this->data as $datum) {
            $sum = $sum->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
            if ($index <= $sum) {
                return $datum[self::USD_KEY];
            }
        }
    }

}
