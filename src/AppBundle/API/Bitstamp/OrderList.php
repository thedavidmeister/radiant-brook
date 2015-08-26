<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStrings;
use AppBundle\Ensure;
use AppBundle\Cast;
use Money\Money;

use function Functional\memoize;

/**
 * Wraps a list of orders provided by Bitstamp to handle some basic statistics.
 *
 * All methods must either return a single pair, a list of pairs or an aggregate
 * value.
 *
 * Most methods on this object maintain an in-memory cache as Money methods are
 * expensive and $data is immutable anyway.
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
     *   a full order book array. $data must be immutable to avoid risks
     *   associated with internal caches on this object.
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
     *
     * $data is immutable once constructed, so we can cache this safely.
     * Caching this removes ~5s from the unit test suite.
     */
    protected function sortUSDAsc()
    {
        if (!isset($this->sortUSDAsc)) {
            // Avoiding closures here helps understand the profiler.
            usort($this->data, [$this, '_sortUSDAscAlgo']);
            $this->sortUSDAsc = $this->data;
        } else {
            $this->data = $this->sortUSDAsc;
        }
    }
    protected function _sortUSDAscAlgo($a, $b) {
        if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
            return 0;
        }

        return $a[self::USD_KEY] < $b[self::USD_KEY] ? -1 : 1;
    }
    protected $sortUSDAsc;

    /**
     * Sorts set by USD desc.
     *
     * $data is immutable once constructed, so we can cache this safely.
     * Caching this removes ~5s from the unit test suite.
     */
    protected function sortUSDDesc()
    {
        if (!isset($this->sortUSDDesc)) {
            // Avoiding closures here helps understand the profiler.
            usort($this->data, [$this, '_sortUSDDescAlgo']);
            $this->sortUSDDesc = $this->data;
        }
        else {
            $this->data = $this->sortUSDDesc;
        }
    }
    protected function _sortUSDDescAlgo($a, $b)
    {
        if ($a[self::USD_KEY] == $b[self::USD_KEY]) {
            return 0;
        }

        return $a[self::USD_KEY] > $b[self::USD_KEY] ? -1 : 1;
    }
    protected $sortUSDDesc;

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
        if (!isset($this->totalVolume)) {
            $volume = array_reduce($this->data, function($carry, $datum) {
                return $carry->add($datum[self::BTC_KEY]);
            }, Money::BTC(0));
            $this->totalVolume = $volume->getAmount();
        }

        return $this->totalVolume;
    }
    protected $totalVolume;

    /**
     * Calculates total capitalisation of the order list.
     *
     * @return int
     *   An aggregate value representing the market cap in USDcentsSatoshis.
     */
    public function totalCap()
    {
        if (!isset($this->totalCap)) {
            $cap = array_reduce($this->data, function($carry, $datum) {
                return $carry->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
            }, Money::USD(0));
            $this->totalCap = $cap->getAmount();
        }

        return $this->totalCap;
    }
    protected $totalCap;

    /**
     * Calculate a percentiles using the "Nearest rank" method.
     *
     * There are multiple ways to calculate percentiles around, the
     * "Nearest rank" method is as follows:
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
     */

    /**
     * Calculates a given percentile based off BTC Volumes.
     *
     * Both percentile functions are inlined for speed after profiling.
     *
     * @see percentileCap()
     *
     * @param float $pc
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return int
     *   An aggregate value representing the USD price of the percentile
     *   calculated against BTC Volume, in USD cents.
     */
    public function percentileBTCVolume($pc)
    {
        $pc = Cast::toFloat($pc);
        Ensure::inRange($pc, 0, 1);

        if (!isset($this->percentileBTCVolumeData)) {
            $this->sortUSDAsc();

            // Build a data array with USD prices as keys and comparison amounts
            // as values.
            $this->percentileBTCVolumeData = array_reduce($this->data, function($carry, $datum) {
                $last = [] === $carry ? Money::BTC(0) : end($carry);
                $carry[$datum[self::USD_KEY]->getAmount()] = $last->add($datum[self::BTC_KEY]);

                return $carry;
            }, []);
        }

        $index = Money::BTC((int) ceil($this->totalVolume() * $pc));

        foreach ($this->percentileBTCVolumeData as $usd => $compare) {
            if ($index->lessThanOrEqual($compare)) {
                return $usd;
            }
        }

        // Catch the edge case where rounding causes $index to overshoot the end
        // of the data set.
        return $usd;
    }
    protected $percentileBTCVolumeData;

    /**
     * Calculates a given percentile based off order list capitalisation.
     *
     * Both percentile functions are inlined for speed after profiling.
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
        $pc = Cast::toFloat($pc);
        Ensure::inRange($pc, 0, 1);

        if (!isset($this->percentileCapData)) {
            $this->sortUSDAsc();

            // Build a data array with USD prices as keys and comparison cap
            // amounts as values.
            $this->percentileCapData = array_reduce($this->data, function ($carry, $datum) {
                // Get the last sum, so we can add to it for a running total.
                $last = [] === $carry ? Money::USD(0) : end($carry);
                $carry[$datum[self::USD_KEY]->getAmount()] = $last->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));

                return $carry;
            }, []);
        }

        $index = Money::USD((int) ceil($this->totalCap() * $pc));
        foreach ($this->percentileCapData as $usd => $compare) {
            if ($index->lessThanOrEqual($compare)) {
                return $usd;
            }
        }

        // Catch the edge case where rounding causes $index to overshoot the end
        // of the data set.
        return $usd;
    }
    protected $percentileCapData;
}
