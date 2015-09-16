<?php
/**
 * @file
 * AppBundle\API\Bitstamp\OrderList
 */

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStringsUtil;
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
                self::USD_KEY => MoneyStringsUtil::stringToUSD($datum[self::USD_PRICE_DATUM_INDEX]),
                self::BTC_KEY => MoneyStringsUtil::stringToBTC($datum[self::BTC_AMOUNT_DATUM_INDEX]),
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
    protected function sortUSDAscAlgo($left, $right)
    {
        // Inlined rather than using Money methods, for speed.
        $leftAmount = $left[self::USD_KEY]->getAmount();
        $rightAmount = $right[self::USD_KEY]->getAmount();

        return ($leftAmount < $rightAmount) ? -1 : (($leftAmount > $rightAmount) ? 1 : 0);
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
    protected function sortUSDDescAlgo($left, $right)
    {
        // Inlined rather than using Money methods, for speed.
        $leftAmount = $left[self::USD_KEY]->getAmount();
        $rightAmount = $right[self::USD_KEY]->getAmount();

        return ($leftAmount > $rightAmount) ? -1 : (($leftAmount < $rightAmount) ? 1 : 0);
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
        return $this->totalCachedReduce(__FUNCTION__, function($carry, $datum) {
            return $carry->add($datum[self::BTC_KEY]);
        }, Money::BTC(0))->getAmount();
    }

    /**
     * Calculates total capitalisation of the order list.
     *
     * @return int
     *   An aggregate value representing the market cap in USDcentsSatoshis.
     */
    public function totalCap()
    {
        return $this->totalCachedReduce(__FUNCTION__, function($carry, $datum) {
            return $carry->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
        }, Money::USD(0))->getAmount();
    }

    /**
     * Array reduce and cache based on provided function.
     * @param string   $name
     *   Cache ID.
     * @param callable $function
     *   Array reduce function.
     * @param mixed    $carry
     *
     * @return mixed
     */
    protected function totalCachedReduce($name, callable $function, $carry)
    {
        v::notEmpty()->string()->check($name);

        if (!isset($this->totalCachedReduce[$name])) {
            // Do the reduce.
            $reduce = array_reduce($this->data, $function, $carry);

            // Cache it.
            $this->totalCachedReduce[$name] = $reduce;
        }

        return $this->totalCachedReduce[$name];
    }
    protected $totalCachedReduce = [];

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
        $index = Money::BTC((int) ceil($this->totalVolume() * $this->percentileCheck($percentile)));

        $compares = $this->buildPercentileCompares(__FUNCTION__, Money::BTC(0), function(array $datum, Money $last) {
            return $last->add($datum[self::BTC_KEY]);
        });

        return $this->percentileIndexCompare($index, $compares);
    }

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
        $index = Money::USD((int) ceil($this->totalCap() * $this->percentileCheck($percentile)));

        $compares = $this->buildPercentileCompares(__FUNCTION__, Money::USD(0), function(array $datum, Money $last) {
            return $last->add($datum[self::USD_KEY]->multiply($datum[self::BTC_KEY]->getAmount()));
        });

        return $this->percentileIndexCompare($index, $compares);
    }

    protected function buildPercentileCompares($name, Money $start, callable $amountCalculator)
    {
        $this->sortUSDAsc();

        return $this->totalCachedReduce($name, function($carry, $datum) use ($start, $amountCalculator) {
            $last = [] === $carry ? $start : end($carry)[self::PERCENTILE_KEY];

            $carry[] = [
                self::USD_KEY => $datum[self::USD_KEY]->getAmount(),
                self::PERCENTILE_KEY => $amountCalculator($datum, $last),
            ];

            return $carry;
        }, []);
    }

    protected function percentileCheck($percentile)
    {
        v::numeric()->between(0, 1, true)->check($percentile);
        $percentile = (float) $percentile;

        return $percentile;
    }

    /**
     * Takes an index and an array of comparisons and returns the percentile.
     *
     * @param Money $index
     *   Money to use as the index.
     *
     * @param array $comparisons
     *   A comparison associative array in the format:
     *     - 'usd' => Scalar amount in cents.
     *     - 'percentile' => Money object representing a percentile.
     *
     * @return int
     *   The integer result of the index comparison.
     */
    protected function percentileIndexCompare(Money $index, array $comparisons)
    {
        v::each(v::instance('Money\Money'))->check(array_map(function($item) {
            return $item['percentile'];
        }, $comparisons));

        v::each(v::int())->check(array_map(function($item) {
            return $item['usd'];
        }, $comparisons));

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
