<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStrings;
use AppBundle\Ensure;
use Money\Money;

/**
 * Wraps a list of orders provided by Bitstamp to handle some basic statistics.
 *
 * All methods must either return a single pair, a list of pairs or an aggregate
 * value.
 */
class OrderList
{
    protected $data = [];

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
    public function __construct(array $data)
    {
        Ensure::notEmpty($data);

        foreach ($data as $datum) {
            $this->data[] = [
                self::USD_KEY => MoneyStrings::stringToUSD($datum[self::USD_PRICE_DATUM_INDEX]),
                self::BTC_KEY => MoneyStrings::stringToBTC($datum[self::BTC_AMOUNT_DATUM_INDEX]),
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
        $index_function = function($pc) {
            return Money::BTC((int) ceil($this->totalVolume() * $pc));
        };
        $sum_init = Money::BTC(0);
        $running_function = function(array $datum, Money $running_sum) {
            return $running_sum->add($datum[self::BTC_KEY]);
        };
        return $this->percentileFinder($pc, $index_function, $sum_init, $running_function);
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
        $index_function = function($pc) {
            return Money::USD((int) ceil($this->totalCap() * $pc));
        };
        $sum_init = Money::USD(0);
        $sum_function = function(array $datum, Money $running_sum) {
            return $running_sum->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
        };
        return $this->percentileFinder($pc, $index_function, $sum_init, $sum_function);
    }

    protected function percentileFinder($pc, callable $index_function, Money $sum_init, callable $sum_function) 
    {
        Ensure::inRange($pc, 0, 1);

        $index = $index_function($pc);
        $this->sortUSDAsc();

        $running_sum = $sum_init;
        foreach ($this->data as $datum) {
            $running_sum = $sum_function($datum, $running_sum);

            if ($index <= $running_sum) {
                // We've found the cap percentile, save it and break loop
                // execution.
                $return = $datum[self::USD_KEY]->getAmount();
                break;
            }
        }

        // it's possible that because of the ceil() in the index generation, the
        // index can be 1 larger than the final sum. In this case, set the
        // percentileCap to the highest data value.
        if (!isset($return)) {
            $return = end($this->data)[self::USD_KEY]->getAmount();
        }

        return $return;
    }

}
