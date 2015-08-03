<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use Money\Money;
use AppBundle\API\Bitstamp\TradePairs\PriceProposer;

use function Functional\first;

/**
 * Suggests and executes profitable trade pairs.
 *
 * The algorithm used for suggesting is:
 *
 * - Get the 5% percentile of bids as a USD price for BTC
 * - Get the minimum USD amount, scaled up to maximum on isofee
 * - Get the volume of BTC purchaseable for chosen USD price & volume
 * - Get the total USD amount, including fees
 *
 * - Get the 5% percentile of asks as a USD price for BTC
 * - Get the USD amount to cover, including bid/ask fees and min USD profit
 * - Get the minimum total BTC volume to sell to cover USD amount, scaled to
 *   minimum isofee
 *
 * - If the USD amount spent in bid can be covered with min USD profit, and the
 *   BTC sold is less than the BTC bought, and there are no dupes, place a pair.
 */
class BitstampTradePairs
{
    const IS_TRADING_SECRET = 'BITSTAMP_IS_TRADING';

    const MIN_BTC_PROFIT_SECRET = 'BITSTAMP_MIN_BTC_PROFIT';

    const PERCENTILE_SECRET = 'BITSTAMP_PERCENTILE';

    // USD has precision of 2.
    const USD_PRECISION = 2;

    /**
     * Constructor to store services passed by Symfony.
     *
     * @param Fees          $fees
     *   Bitstamp Fees service.
     *
     * @param Dupes         $dupes
     *   Bitstamp Dupes service.
     *
     * @param BuySell       $buySell
     *   Combined Bitstamp buy/sell service.
     *
     * @param PriceProposer $proposer
     *   Bitstamp proposer service.
     */
    public function __construct(
        Fees $fees,
        Dupes $dupes,
        BuySell $buySell,
        PriceProposer $proposer
    )
    {
        $this->fees = $fees;
        $this->dupes = $dupes;
        $this->buySell = $buySell;
        $this->proposer = $proposer;
        $this->secrets = new Secrets();
    }

    /**
     * BIDS
     */


    /**
     * ASKS
     */

    /**
     * How much USD can we keep from our sale, post fees?
     *
     * @return Money::USD
     */
    public function volumeUSDAskPostFees()
    {
        return Money::USD((int) floor($this->volumeUSDAsk()->getAmount() * $this->fees->asksMultiplier()));
    }

    /**
     * DIFF
     */

    /**
     * Returns the minimum acceptable BTC profit for a valid pair.
     *
     * @return Money::BTC
     */
    public function minProfitBTC()
    {
        $minProfitBTC = $this->secrets->get(self::MIN_BTC_PROFIT_SECRET);

        if (filter_var($minProfitBTC, FILTER_VALIDATE_INT) === false) {
            throw new \Exception('Minimum BTC profit configuration must be an integer value. data: ' . print_r($minProfitBTC, true));
        }

        return Money::BTC((int) $minProfitBTC);
    }

    /**
     * Returns the BTC profit of the suggested pair.
     *
     * @return Money::BTC
     */
    public function profitBTC()
    {
        return Money::BTC((int) floor($this->bidBTCVolume()->getAmount() - $this->askBTCVolume()->getAmount()));
    }

    /**
     * Returns the minimum acceptable USD profit for a valid pair.
     *
     * @return Money::USD
     */
    public function minProfitUSD()
    {
        $minProfitUSD = $this->secrets->get(self::MIN_USD_PROFIT_SECRET);

        if (filter_var($minProfitUSD, FILTER_VALIDATE_INT) === false) {
            throw new \Exception('Minimum USD profit configuration must be an integer value. data: ' . print_r($minProfitUSD, true));
        }

        return Money::USD((int) $minProfitUSD);
    }

    /**
     * Returns the USD profit of the suggested pair.
     *
     * @return Money::USD
     */
    public function profitUSD()
    {
        return Money::USD((int) floor($this->volumeUSDAskPostFees()->getAmount() - $this->volumeUSDBidPostFees()->getAmount()));
    }

    /**
     * Returns the average of the bid and ask price.
     *
     * @return Money::USD
     */
    public function midprice()
    {
        $midpoint = (int) round(($this->bidPrice()->getAmount() + $this->askPrice()->getAmount()) / 2, self::USD_PRECISION);

        return Money::USD($midpoint);
    }

    /**
     * Execute the suggested trade pairs with Bitstamp.
     *
     * If $this fails validation, the trade pairs will not be executed and an
     * exception thrown, to protect against unprofitable and duplicate orders.
     */
    public function execute()
    {
        foreach ($this->proposer as $proposition) {
            print_r($proposition);
        }
    }

    /**
     * Does the pair meet all requirements for execution?
     *
     * @return bool
     */
    public function ensureValid()
    {
        $errors = [];

        // break statements are intentionally left out here to allow multiple
        // error messages to be collated.
        switch (false) {
            case $this->isTrading():
                $errors[] = 'Bitstamp trading is disabled at this time.';
            case $this->isProfitable():
                $errors[] = 'No profitable trade pairs found.';
            case !$this->hasDupes():
                $errors[] = 'Duplicate trade pairs found';
        }

        if (!empty($errors)) {
            throw new \Exception('Invalid trade pairs: ' . implode(' ', $errors));
        }

        return true;
    }

    /**
     * Is trading currently enabled?
     *
     * The following values for the BITSTAMP_IS_TRADING environment variable is
     * supported as true:
     * - '1'
     * - 'true'
     * - 'yes'
     *
     * @return bool
     *   true if trading is enabled.
     */
    public function isTrading()
    {
        $isTrading = $this->secrets->get(self::IS_TRADING_SECRET);

        return filter_var($isTrading, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Is the suggested pair profitable?
     *
     * @return bool
     */
    public function isProfitable()
    {
        return $this->profitUSD() >= $this->minProfitUSD() && $this->profitBTC() > $this->minProfitBTC();
    }

    /**
     * Does the pair duplicate open orders on either leg?
     *
     * @return bool
     */
    public function hasDupes()
    {
        return !empty($this->dupes->bids($this->bidPrice())) || !empty($this->dupes->asks($this->askPrice()));
    }
}
