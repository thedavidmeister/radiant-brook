<?php
/**
 * @file
 * AppBundle\API\Bitstamp\OrderList
 */

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStrings;
use Money\Money;
use Respect\Validation\Validator as v;

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

    const PERCENTILE_KEY = 'percentile';

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
        v::notEmpty()->check($data);

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
            usort($this->data, [$this, 'sortUSDAscAlgo']);
            $this->sortUSDAsc = $this->data;
        } else {
            $this->data = $this->sortUSDAsc;
        }
    }
    protected function sortUSDAscAlgo($a, $b)
    {
        // Inlined rather than using Money methods, for speed.
        $aAmount = $a[self::USD_KEY]->getAmount();
        $bAmount = $b[self::USD_KEY]->getAmount();

        return ($aAmount < $bAmount) ? -1 : (($aAmount > $bAmount) ? 1 : 0);
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
            usort($this->data, [$this, 'sortUSDDescAlgo']);
            $this->sortUSDDesc = $this->data;
        } else {
            $this->data = $this->sortUSDDesc;
        }
    }
    protected function sortUSDDescAlgo($a, $b)
    {
        // Inlined rather than using Money methods, for speed.
        $aAmount = $a[self::USD_KEY]->getAmount();
        $bAmount = $b[self::USD_KEY]->getAmount();

        return ($aAmount > $bAmount) ? -1 : (($aAmount < $bAmount) ? 1 : 0);
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
     * @param float $percentile
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return int
     *   An aggregate value representing the USD price of the percentile
     *   calculated against BTC Volume, in USD cents.
     */
    public function percentileBTCVolume($percentile)
    {
        v::numeric()->between(0, 1, true)->check($percentile);
        $percentile = (float) $percentile;

        if (!isset($this->percentileBTCVolumeData)) {
            $this->sortUSDAsc();

            // Build a data array with USD prices as keys and comparison amounts
            // as values.
            $this->percentileBTCVolumeData = array_reduce($this->data, function($carry, $datum) {
                // Get the last sum, so we can add a running total.
                $last = [] === $carry ? Money::BTC(0) : end($carry)[self::PERCENTILE_KEY];

                // Build a simple array we can compare the index against.
                $compare = [
                    self::USD_KEY => $datum[self::USD_KEY]->getAmount(),
                    self::PERCENTILE_KEY => $last->add($datum[self::BTC_KEY]),
                ];

                // Add to the carry.
                $carry[] = $compare;

                return $carry;
            }, []);
        }

        $index = Money::BTC((int) ceil($this->totalVolume() * $percentile));

        return $this->percentileIndexCompare($index, $this->percentileBTCVolumeData);
    }
    protected $percentileBTCVolumeData;

    /**
     * Calculates a given percentile based off order list capitalisation.
     *
     * Both percentile functions are inlined for speed after profiling.
     *
     * @see percentileBTCVolume()
     *
     * @param float $percentile
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return int
     *   An aggregate value representing the USD price of the percentile
     *   calculated against market cap, in USD cents.
     */
    public function percentileCap($percentile)
    {
        v::numeric()->between(0, 1, true)->check($percentile);
        $percentile = (float) $percentile;

        if (!isset($this->percentileCapData)) {
            $this->sortUSDAsc();

            // Build a data array with USD prices as keys and comparison cap
            // amounts as values.
            $this->percentileCapData = array_reduce($this->data, function($carry, $datum) {
                // Get the last sum, so we can add to it for a running total.
                $last = [] === $carry ? Money::USD(0) : end($carry)[self::PERCENTILE_KEY];

                // Build a simple array we can compare the index against.
                $compare = [
                    self::USD_KEY => $datum[self::USD_KEY]->getAmount(),
                    self::PERCENTILE_KEY => $last->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount())),
                ];

                // Add to the carry.
                $carry[] = $compare;

                return $carry;
            }, []);
        }

        $index = Money::USD((int) ceil($this->totalCap() * $percentile));

        return $this->percentileIndexCompare($index, $this->percentileCapData);
    }
    protected $percentileCapData;

    protected function percentileIndexCompare($index, $comparisons)
    {
        // Ensure index cannot overshoot data set.
        if ($index->greaterThanOrEqual(end($comparisons)[self::PERCENTILE_KEY])) {
            $index = end($comparisons)[self::PERCENTILE_KEY];
        }

        // Remove every element that is below the index.
        $noBelowIndex = array_filter($comparisons, function($compare) use ($index) {
            return $index->lessThanOrEqual($compare[self::PERCENTILE_KEY]);
        });

        // Return the lowest element remaining.
        return reset($noBelowIndex)[self::USD_KEY];
    }
}
