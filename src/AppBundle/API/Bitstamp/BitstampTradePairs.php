<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\Secrets;
use Money\Money;

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
    const MIN_USD_VOLUME_SECRET = 'BITSTAMP_MIN_USD_VOLUME';

    const MIN_USD_PROFIT_SECRET = 'BITSTAMP_MIN_USD_PROFIT';

    const MIN_BTC_PROFIT_SECRET = 'BITSTAMP_MIN_BTC_PROFIT';

    const PERCENTILE_SECRET = 'BITSTAMP_PERCENTILE';

    // Bitcoin has precision of 8.
    const BTC_PRECISION = 8;

    // USD has precision of 2.
    const USD_PRECISION = 2;

    // The minimum amount of USD cents profit we need to commit to a pair.
    const MIN_PROFIT_USD = 1;

    /**
     * Constructor to store services passed by Symfony.
     *
     * @param Fees      $fees
     *   Bitstamp Fees service.
     *
     * @param Dupes     $dupes
     *   Bitstamp Dupes service.
     *
     * @param BuySell   $buySell
     *   Combined Bitstamp buy/sell service.
     *
     * @param OrderBook $orderbook
     *   Bitstamp order book service.
     */
    public function __construct(
        Fees $fees,
        Dupes $dupes,
        BuySell $buySell,
        PublicAPI\OrderBook $orderbook
    )
    {
        $this->fees = $fees;
        $this->dupes = $dupes;
        $this->buySell = $buySell;
        $this->orderBook = $orderbook;
    }

    /**
     * BIDS
     */

    /**
     * The base USD volume from config pre-isofee scaling.
     *
     * @return Money::USD
     */
    public function baseVolumeUSDBid()
    {
        $secrets = new Secrets();

        return Money::USD((int) $secrets->get(self::MIN_USD_VOLUME_SECRET));
    }

    /**
     * The USD bid volume pre-fees.
     *
     * We can simply scale the minimum USD volume allowable using the fee
     * structure as a limit.
     *
     * @return Money::USD
     */
    public function volumeUSDBid()
    {
        return $this->fees->isofeeMaxUSD($this->baseVolumeUSDBid());
    }

    /**
     * The bid USD price of the suggested pair.
     *
     * For bids, we use the cap percentile as it's harder for other users to
     * manipulate and we want 1 - PERCENTILE as bids are decending.
     *
     * @return Money::USD
     */
    public function bidPrice()
    {
        $secrets = new Secrets();

        return Money::USD($this->orderBook->bids()->percentileCap(1 - $secrets->get(self::PERCENTILE_SECRET)));
    }

    /**
     * The USD bid volume including fees.
     *
     * We can simply add the fees for this USD volume to the USD volume.
     *
     * @return Money::USD
     */
    public function volumeUSDBidPostFees()
    {
        return $this->volumeUSDBid()->add($this->fees->absoluteFeeUSD($this->volumeUSDBid()));
    }

    /**
     * The bid BTC volume of the suggested pair.
     *
     * The volume of BTC is simply the amount of USD we have to spend divided by
     * the amount we're willing to spend per Satoshi.
     *
     * @return Money::BTC
     */
    public function bidBTCVolume()
    {
        // Its very important that when we lodge our bid with Bitstamp, the
        // BTC volume times the USD price does not exceed the maximum USD volume
        // on the isofee. For this reason, we floor any fractions of satoshis
        // that come out of this equation to avoid any risk of being one satoshi
        // over the limit from Bitstamp's perspective.
        //
        // For this reason we do NOT use something like MoneyStrings.
        $satoshis = (int) floor(($this->volumeUSDBid()->getAmount() / $this->bidPrice()->getAmount()) * (10 ** self::BTC_PRECISION));

        // This must never happen.
        if ($satoshis * $this->bidPrice()->getAmount() / (10 ** self::BTC_PRECISION) > $this->volumeUSDBid()->getAmount()) {
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->bidPrice()->getAmount() . ' per BTC which exceeds allowed volume USD ' . $this->volumeUSDBid()->getAmount());
        }

        return Money::BTC($satoshis);
    }

    /**
     * ASKS
     */

    /**
     * The asking USD price in the suggested pair.
     *
     * For asks, we use the BTC volume percentile as it's harder for other users
     * to manipulate. Asks are sorted ascending so we can use $pc directly.
     *
     * @return Money::USD
     */
    public function askPrice()
    {
        $secrets = new Secrets();

        return Money::USD($this->orderBook->asks()->percentileCap($secrets->get(self::PERCENTILE_SECRET)));
    }

    /**
     * Returns the USD volume required to cover the bid USD + fees.
     *
     * The volume USD that we get to keep K is:
     *   - X = USD value of BTC sold
     *   - Fa = Fee asks multiplier
     *   - K = X * Fa
     *
     * If we want to keep enough to cover our total bid cost B + profit P then:
     *   - K = B + P
     *
     * Therefore:
     *   - B + P = X * Fa
     *   - X = (B + P) / Fa
     *
     * @return Money::USD
     */
    public function volumeUSDAsk()
    {
        $x = ($this->volumeUSDBidPostFees()->getAmount() + self::MIN_PROFIT_USD) / $this->fees->asksMultiplier();

        // We have to ceil() $x or risk losing our USD profit to fees.
        return Money::USD((int) ceil($x));
    }

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
     * The asking volume of BTC in the suggested pair.
     *
     * BTC volume is simply the amount of USD we need to sell divided by the
     * USD price per BTC.
     *
     * @return Money::BTC
     */
    public function askBTCVolume()
    {
        // We have to ceiling our satoshis to guarantee that we meet our minimum
        // ask USD volume, or we risk fees killing our profits.
        $satoshis = (int) ceil($this->volumeUSDAsk()->getAmount() / $this->askPrice()->getAmount() * 10 ** self::BTC_PRECISION);

        // This must never happen.
        if ($satoshis * $this->askPrice()->getAmount() / (10 ** self::BTC_PRECISION) < $this->volumeUSDAsk()->getAmount()) {
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->askPrice()->getAmount() . ' per BTC which does not meet required volume USD ' . $this->volumeUSDAsk()->getAmount());
        }

        return Money::BTC($satoshis);
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
        $secrets = new Secrets();
        $minProfitBTC = $secrets->get(self::MIN_BTC_PROFIT_SECRET);

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
        $secrets = new Secrets();
        $minProfitUSD = $secrets->get(self::MIN_USD_PROFIT_SECRET);

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
        if ($this->isValid()) {
            $this->buySell->execute($this->bidPrice(), $this->bidBTCVolume(), $this->askPrice(), $this->askBTCVolume());
        } else {
            throw new \Exception('It is not safe to execute a trade pair at this time.');
        }
    }

    /**
     * Does the pair meet all requirements for execution?
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->isProfitable() && !$this->hasDupes();
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
