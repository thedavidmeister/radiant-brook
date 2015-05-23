<?php

namespace AppBundle\API\Bitstamp;

use Symfony\Component\Validator\Constraints as Assert;
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

    protected $_fee;

    protected $_volume;

    // As of May 15, 2014 the minimum allowable trade will be USD $5.
    const MIN_VOLUME_USD = 500;

    // Bitstamp limits the fidelity of BTC trades.
    const BTC_PRECISION = 8;

    // The percentile of cap/volume we'd like to trade to.
    const PERCENTILE = 0.05;

    // The minimum amount of USD cents profit we need to commit to a pair.
    const MIN_PROFIT_USD = 1;

    // Multiplier on a bid/ask price to consider it a dupe with existing orders.
    const DUPE_RANGE_MULTIPLIER = 0.01;

    /**
     * Constructor to store services passed by Symfony.
     * @param Balance                                         $balance
     *   Bitstamp balance service.
     *
     * @param OrderBook                                       $orderbook
     *   Bitstamp order book service.
     *
     * @param OpenOrders                                      $openorders
     *   Bitstamp open orders service.
     *
     * @param Sell                                            $sell
     *   Bitstamp sell service.
     *
     * @param Buy                                             $buy
     *   Bitstamp buy service.
     *
     * @param \Symfony\Component\Validator\ValidatorInterface $validator
     *   Symfony validator service.
     *
     * @param \Psr\Log\LoggerInterface                        $logger
     *   Symfony logger service.
     */
    public function __construct(
    Fees $fees,
    PublicAPI\OrderBook $orderbook,
    PrivateAPI\OpenOrders $openorders,
    PrivateAPI\Sell $sell,
    PrivateAPI\Buy $buy,
    \Symfony\Component\Validator\ValidatorInterface $validator,
    \Psr\Log\LoggerInterface $logger)
    {
        $this->fees = $fees;
        $this->orderBook = $orderbook;
        $this->openOrders = $openorders;
        $this->sell = $sell;
        $this->buy = $buy;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * BIDS
     */

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
        return $this->fees->isofeeMaxUSD(Money::USD(self::MIN_VOLUME_USD));
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
        return Money::USD($this->orderBook->bids()->percentileCap(1 - self::PERCENTILE));
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
        return Money::USD($this->orderBook->asks()->percentileCap($this::PERCENTILE));
    }

    /**
     * Returns the USD volume required to cover the bid USD + fees.
     *
     * The volume USD that we get to keep K is:
     *   - X = USD value of BTC sold
     *   - F = Fee multiplier
     *   - K = X - (X * F)
     *
     * If we want to keep enough to cover our total bid cost B + profit P then:
     *   - K = B + P
     *
     * Therefore:
     *   - B + P = X - (X * F)
     *   - B + P = X(1 - F)
     *   - X = (B + P) / (1 - F)
     *
     * @return float
     */
    public function volumeUSDAsk()
    {
        $X = ($this->volumeUSDBidPostFees()->getAmount() + self::MIN_PROFIT_USD) / (1 - $this->fees->multiplier());
        // We have to ceil() $X or risk losing our USD profit to fees.
        return Money::USD((int) ceil($X));
    }

    /**
     * The asking USD Volume required to cover fees.
     *
     * @return float
     */
    public function volumeUSDAskPostFees()
    {
        return floor($this->askBTCVolume() * $this->askPrice()->getAmount() * (1 - $this->fees->multiplier()) * 100) / 100;
    }

    /**
     * The asking volume of BTC in the suggested pair.
     *
     * @return float
     */
    public function askBTCVolume()
    {
        $rounded = round($this->volumeUSDAsk()->getAmount() / $this->askPrice()->getAmount(), self::BTC_PRECISION);
        // @see bidBTCVolume()
        if (($rounded * $this->askPrice()->getAmount()) < $this->volumeUSDAsk()->getAmount()) {
            $rounded += 10 ** -($this::BTC_PRECISION - 1);
        }

        return $rounded;
    }

    /**
     * The effective ask price is post-fees.
     */
    protected function askPriceEffective()
    {
        return ($this->askPrice() * $this->askBTCVolume() - $this->feeAbsolute()) / $this->askBTCVolume();
    }

    /**
     * DIFF
     */

    /**
     * Returns the BTC profit of the suggested pair.
     *
     * @return float
     */
    public function profitBTC()
    {
        return $this->bidBTCVolume()->getAmount() * (1 - $this->fees->multiplier()) - $this->askBTCVolume() * (1 + $this->fees->multiplier());
    }

    /**
     * Returns the USD profit of the suggested pair.
     *
     * @return float
     */
    public function profitUSD()
    {
        return floor(($this->volumeUSDAskPostFees() - $this->volumeUSDBidPostFees()->getAmount()) * 100) / 100;
    }

    /**
     * Returns the average of the bid and ask price.
     *
     * @return float
     */
    public function midprice()
    {
        return ($this->bidPrice()->getAmount() + $this->askPrice()->getAmount()) / 2;
    }

    /**
     * Returns open orders that duplicate either leg of the suggested pair.
     *
     * @return array
     *   A list of open duplicate bids and asks.
     */
    public function dupes()
    {
        $baseSearchParams = [
        'key' => 'price',
        'unit' => '=',
        'operator' => '~',
        ];

        $bidDupes = $this->openOrders->search([
            'range' => $this->bidPrice()->getAmount() * $this::DUPE_RANGE_MULTIPLIER,
            'value' => $this->bidPrice()->getAmount(),
            'type' => $this->openOrders->typeBuy(),
        ] + $baseSearchParams);

        $askDupes = $this->openOrders->search([
            'range' => $this->askPrice()->getAmount() * $this::DUPE_RANGE_MULTIPLIER,
            'value' => $this->askPrice()->getAmount(),
            'type' => $this->openOrders->typeSell(),
        ] + $baseSearchParams);

        return [
        'bids' => $bidDupes,
        'asks' => $askDupes,
        ];
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
            $this->sell
            ->setParam('price', $this->askPrice())
            ->setParam('amount', $this->askBTCVolume())
            ->execute();

            $this->buy
            ->setParam('price', $this->bidPrice())
            ->setParam('amount', $this->bidBTCVolume())
            ->execute();

            $this->logger->info('Trade pairs executed');
        } else {
            // @todo - log the reasons?
            $e = new \Exception('It is not safe to execute a trade pair at this time.');
            $this->logger->error('It is not safe to execute a trade pair at this time.', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Validates the proposed trade pairs.
     *
     * @return boolean true if valid.
     */
    public function isValid()
    {
        return !count($this->validator->validate($this));
    }

    /**
     * Asserts the suggested pairs are profitable.
     *
     * @Assert\True(message="This trade is not profitable")
     *
     * @return bool
     */
    public function isProfitable()
    {
        return $this->profitUSD() >= round($this::MIN_PROFIT_USD, 2) && $this->profitBTC() > 0;
    }

    /**
     * Asserts there are no duplicate open orders with the suggested pairs.
     *
     * @assert\False(message="There are currently dupes")
     *
     * @return bool
     */
    public function hasDupes()
    {
        return !empty($this->dupes()['bids']) || !empty($this->dupes()['asks']);
    }
}
