<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use Money\Money;

/**
 * AppBundle\API\Bitstamp\TracePairs\PriceProposer.
 */
class PriceProposer implements \Iterator
{
    const MIN_USD_PROFIT_SECRET = 'BITSTAMP_MIN_USD_PROFIT';

    const MIN_PERCENTILE_SECRET = 'BITSTAMP_PERCENTILE_MIN';

    const MAX_PERCENTILE_SECRET = 'BITSTAMP_PERCENTILE_MAX';

    const STEP_SIZE_SECRET = 'BITSTAMP_PERCENTILE_STEP';

    protected $minPercentile;

    protected $maxPercentile;

    protected $stepSize;

    protected $currentPercentile;

    protected $orderBook;


    /**
     * DI Constructor.
     *
     * @param \AppBundle\API\Bitstamp\PublicAPI\OrderBook $orderBook
     */
    public function __construct(
        \AppBundle\API\Bitstamp\PublicAPI\OrderBook $orderBook
    )
    {
        // DI.
        $this->orderBook = $orderBook;

        // Secrets.
        $this->secrets = new Secrets();

        // Init.
        $this->minPercentile = $this->secrets->get(self::MIN_PERCENTILE_SECRET);
        $this->maxPercentile = $this->secrets->get(self::MAX_PERCENTILE_SECRET);
        $this->stepSize = $this->secrets->get(self::STEP_SIZE_SECRET);
    }

    /**
     * The bid USD price of the suggested pair.
     *
     * For bids, we use the cap percentile as it's harder for other users to
     * manipulate and we want 1 - PERCENTILE as bids are decending.
     *
     * @return Money::USD
     */
    public function bidUSDPrice()
    {
        return Money::USD($this->orderBook->bids()->percentileCap(1 - $this->currentPercentile));
    }

    /**
     * The asking USD price in the suggested pair.
     *
     * For asks, we use the BTC volume percentile as it's harder for other users
     * to manipulate. Asks are sorted ascending so we can use $pc directly.
     *
     * @return Money::USD
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
     * @return mixed
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
            && $this->currentPercentile >= $this->minPercentile
            && is_numeric($this->currentPercentile)
            && is_numeric($this->minPercentile)
            && is_numeric($this->maxPercentile);
    }
}
