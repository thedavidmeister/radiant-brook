<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use Money\Money;
use Respect\Validation\Validator as v;

/**
 * Iterate over percentiles straight from the OrderBook.
 */
class PriceProposer implements \Iterator
{

    protected $minPercentile;

    protected $maxPercentile;

    protected $stepSize;

    protected $currentPercentile;

    protected $orderBook;

    /**
     * DI Constructor.
     *
     * @param \AppBundle\API\Bitstamp\PublicAPI\OrderBook $orderBook
     *
     * @param array                                       $minMaxStep
     *   An array with 3 floats representing the minimumPercentile,
     *   maximumPercentile and stepSize (in that order).
     */
    public function __construct(
        \AppBundle\API\Bitstamp\PublicAPI\OrderBook $orderBook,
        array $minMaxStep
    )
    {
        // DI.
        $this->orderBook = $orderBook;

        // Ensure minMaxStep is 3 floats.
        v::length(3, 3, true)
            ->each(v::finite())
            ->check($minMaxStep);

        // It is easier to compare floats as strings, weirdly.
        $minMaxStep = array_map(function($item) {
            return (string) $item;
        }, $minMaxStep);

        // Ensure that the minimum for maxPercentile is minPercentile
        v::min($minMaxStep[0])->check($minMaxStep[1]);

        // Ensure that the step size is less than min - max.
        $minMaxDiff = (string) ($minMaxStep[1] - $minMaxStep[0]);
        v::max($minMaxDiff, true)->check($minMaxStep[2]);

        $diffStepRatio = $minMaxDiff / $minMaxStep[2];
        if ((int) (string) $diffStepRatio != (string) $diffStepRatio) {
            throw new \Exception('Step size ' . $minMaxStep[2] . ' does not divide evenly into ' . $minMaxDiff . '. Ratio ' . $diffStepRatio . ' must be an integer.');
        }

        list($this->minPercentile, $this->maxPercentile, $this->stepSize) = $minMaxStep;

        // Start at the start. Auto rewind! Reconsider this if setting
        // percentiles and step sizes rather than using environment variables.
        $this->rewind();
    }

    /**
     * Read-only minPercentile.
     *
     * @return float
     */
    public function minPercentile()
    {
        return $this->minPercentile;
    }

    /**
     * Read-only maxPercentile.
     *
     * @return integer|double
     */
    public function maxPercentile()
    {
        return $this->maxPercentile;
    }

    /**
     * Read-only stepSize.
     *
     * @return float
     */
    public function stepSize()
    {
        return $this->stepSize;
    }

    /**
     * The bid USD price of the suggested pair.
     *
     * For bids, we use the cap percentile as it's harder for other users to
     * manipulate and we want 1 - PERCENTILE as bids are decending.
     *
     * @return Money
     */
    public function bidUSDPrice()
    {
        return Money::USD($this->orderBook->bids()->percentileCap(1 - $this->currentPercentile));
    }

    /**
     * The asking USD price in the suggested pair.
     *
     * For asks, we use the BTC volume percentile as it's harder for other users
     * to manipulate. Asks are sorted ascending so we can use $percentile
     * directly.
     *
     * @return Money
     */
    public function askUSDPrice()
    {
        return Money::USD($this->orderBook->asks()->percentileCap($this->currentPercentile));
    }

    /**
     * Return the current element.
     *
     * @see http://php.net/manual/en/iterator.current.php
     *
     * @return array<string,Money>
     */
    public function current()
    {
        return [
            'bidUSDPrice' => $this->bidUSDPrice(),
            'askUSDPrice' => $this->askUSDPrice(),
        ];
    }

    /**
     * Return the key of the current element
     *
     * @see http://php.net/manual/en/iterator.key.php
     *
     * @return scalar|null
     *   Returns scalar on success, or NULL on failure.
     */
    public function key()
    {
        return $this->currentPercentile;
    }

    /**
     * Move forward to next element.
     *
     * Moves the current position to the next element.
     *
     * @see http://php.net/manual/en/iterator.next.php
     */
    public function next()
    {
        $this->currentPercentile += $this->stepSize;
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * Rewinds back to the first element of the Iterator.
     *
     * @see http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        $this->currentPercentile = $this->minPercentile;
    }

    /**
     * Checks if current position is valid.
     *
     * This method is called after Iterator::rewind() and Iterator::next() to
     * check if the current position is valid.
     *
     * @see http://php.net/manual/en/iterator.valid.php
     *
     * @return bool
     *   The return value will be casted to boolean and then evaluated. Returns
     *   TRUE on success or FALSE on failure.
     */
    public function valid()
    {
        return $this->currentPercentile <= $this->maxPercentile
            && $this->currentPercentile >= $this->minPercentile;
    }
}
