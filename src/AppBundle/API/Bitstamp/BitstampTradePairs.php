<?php

namespace AppBundle\API\Bitstamp;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Suggests and executes profitable trade pairs.
 */
class BitstampTradePairs
{

    protected $_fee;

    protected $_volume;

    // As of May 15, 2014 the minimum allowable trade will be USD $5.
    const MIN_VOLUME_USD = 5;

    // Bitstamp limits the fidelity of BTC trades.
    const BTC_FIDELITY = 8;

    // The percentile of cap/volume we'd like to trade to.
    const PERCENTILE = 0.05;

    // The minimum amount of USD profit we need to commit to a pair.
    const MIN_PROFIT_USD = 0.01;

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
    PrivateAPI\Balance $balance,
    PublicAPI\OrderBook $orderbook,
    PrivateAPI\OpenOrders $openorders,
    PrivateAPI\Sell $sell,
    PrivateAPI\Buy $buy,
    \Symfony\Component\Validator\ValidatorInterface $validator,
    \Psr\Log\LoggerInterface $logger)
    {
        $this->balance = $balance;
        $this->orderBook = $orderbook;
        $this->openOrders = $openorders;
        $this->sell = $sell;
        $this->buy = $buy;
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * Returns the DateTime object for the requested service.
     *
     * Will be based on execute() or data() as appropriate.
     *
     * @param string $service
     *   The name of the service to get the DateTime for.
     *
     * @return DateTime
     */
    public function datetime($service)
    {
        return $this->{$service}->datetime();
    }

    /**
     * The percentage fee charged by Bitstamp for our user account.
     *
     * @return float
     */
    public function fee()
    {
        if (!isset($this->_fee)) {
            // Bitstamp sends us the fee as a percentage represented as a decimal,
            // e.g. 0.25% is handed to us as 0.25 rather than 0.0025, which will make
            // all subsequent math difficult, so it's worth massaging the value here.
            $this->_fee = $this->balance->execute()['fee'] / 100;
        }

        return $this->_fee;
    }

    /**
     * The USD bid volume pre-fees.
     *
     * @return float
     */
    public function volumeUSDBid()
    {
        if (!isset($this->_volume)) {
            // Start with the minimum volume allowable.
            $volume = $this::MIN_VOLUME_USD;

            // Get the fee percentage.
            $fee = $this->fee();

            // Calculate the absolute fee at the min USD volume.
            $feeAbsolute = $volume * $fee;

            // We kindly ask our users to take note on Bitstamp's policy regarding fee
            // calculation. As our fees are calculated to two decimal places, all fees
            // which might exceed this limitation are rounded up. The rounding up is
            // executed in such a way, that the second decimal digit is always one
            // digit value higher than it was before the rounding up. For example; a
            // fee of 0.111 will be charged as 0.12.
            // @see https://www.bitstamp.net/fee_schedule/
            $feeAbsoluteRounded = ceil($feeAbsolute * 100) / 100;

            // We can bump our volume up to the next integer fee value without
            // incurring extra cost to achieve improved effective prices.
            $volumeAdjusted = ($feeAbsoluteRounded / $feeAbsolute) * $volume;

            $this->_volume = $volumeAdjusted;
        }

        return $this->_volume;
    }

    /**
     * The USD bid volume required to cover fees.
     *
     * @return float
     */
    public function volumeUSDBidPostFees()
    {
        return ceil($this->bidBTCVolume() * $this->bidPrice() * (1 + $this->fee()) * 100) / 100;
    }

    /**
     * Returns the USD volume required to cover the bid USD + fees.
     *
     * @return float
     */
    public function volumeUSDAsk()
    {
        // @todo - Is (1 + $this->fee() * 2) correct?
        return $this->volumeUSDBidPostFees() * (1 + $this->fee() * 2) + $this::MIN_PROFIT_USD;
    }

    /**
     * The absolute USD value of fees in cents.
     *
     * Handles the weird Bitstamp rounding policy.
     *
     * We can't use this inside volumeUSDBid because we'd have circular deps.
     *
     * @return int
     */
    protected function feeAbsolute()
    {
        return ceil($this->volumeUSDBid() * $this->fee() * 100) / 100;
    }

    /**
     * BIDS
     */

    /**
     * The bid USD price of the suggested pair.
     *
     * @return float
     */
    public function bidPrice()
    {
        // For bids, we use the cap percentile as it's harder for other users to
        // manipulate and we want 1 - PERCENTILE as bids are decending.
        return $this->orderBook->bids()->percentCap(1 - $this::PERCENTILE)[0];
    }

    /**
     * The bid BTC volume of the suggested pair.
     *
     * @todo test this lots.
     *
     * @return float
     */
    public function bidBTCVolume()
    {
        $rounded = round($this->volumeUSDBid() / $this->bidPrice(), $this::BTC_FIDELITY);
        // Its very important that when we lodge our bid with Bitstamp, the volume
        // times the price does not exceed the USD volume cap for the current fee,
        // or we pay the fee for the next bracket for no price advantage.
        if (($rounded * $this->bidPrice()) > $this->volumeUSDBid()) {
            $rounded -= 10 ** -($this::BTC_FIDELITY - 1);
        }

        return $rounded;
    }

    /**
     * The effective USD bid price includes fees.
     *
     * @return float
     */
    protected function bidPriceEffective()
    {
        return ($this->bidPrice() * $this->bidBTCVolume() + $this->feeAbsolute()) / $this->bidBTCVolume();
    }

    /**
   * ASKS
   */

    /**
     * The asking USD Volume required to cover fees.
     *
     * @return float
     */
    public function volumeUSDAskPostFees()
    {
        return floor($this->askBTCVolume() * $this->askPrice() * (1 - $this->fee()) * 100) / 100;
    }

    /**
     * The asking USD price in the suggested pair.
     *
     * @return float
     */
    public function askPrice()
    {
        // For asks, we use the BTC volume percentile as it's harder for other users
        // to manipulate. Asks are sorted ascending so we can use $pc directly.
        return $this->orderBook->asks()->percentile($this::PERCENTILE)[0];
    }

    /**
     * The asking volume of BTC in the suggested pair.
     *
     * @return float
     */
    public function askBTCVolume()
    {
        $rounded = round($this->volumeUSDAsk() / $this->askPrice(), $this::BTC_FIDELITY);
        // @see bidBTCVolume()
        if (($rounded * $this->askPrice()) < $this->volumeUSDAsk()) {
            $rounded += 10 ** -($this::BTC_FIDELITY - 1);
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
        return $this->bidBTCVolume() * (1 - $this->fee()) - $this->askBTCVolume() * (1 + $this->fee());
    }

    /**
     * Returns the USD profit of the suggested pair.
     *
     * @return float
     */
    public function profitUSD()
    {
        return floor(($this->volumeUSDAskPostFees() - $this->volumeUSDBidPostFees()) * 100) / 100;
    }

    /**
     * Returns the average of the bid and ask price.
     *
     * @return float
     */
    public function midprice()
    {
        return ($this->bidPrice() + $this->askPrice()) / 2;
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
            'range' => $this->bidPrice() * $this::DUPE_RANGE_MULTIPLIER,
            'value' => $this->bidPrice(),
            'type' => $this->openOrders->typeBuy(),
        ] + $baseSearchParams);

        $askDupes = $this->openOrders->search([
            'range' => $this->askPrice() * $this::DUPE_RANGE_MULTIPLIER,
            'value' => $this->askPrice(),
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
