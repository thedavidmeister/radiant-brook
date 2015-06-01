<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStrings;
use Money\Money;

/**
 * Convenience class to lodge a buy and sell pair simultaneously.
 */
class BuySell
{
    /**
     * DI constructor.
     *
     * @param PrivateAPI\Buy           $buy
     * @param PrivateAPI\Sell          $sell
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
      PrivateAPI\Buy $buy,
      PrivateAPI\Sell $sell,
      \Psr\Log\LoggerInterface $logger
    )
    {
        $this->buy = $buy;
        $this->sell = $sell;
        $this->logger = $logger;
    }

    const PRICE_KEY = 'price';

    const VOLUME_KEY = 'amount';

    /**
     * Executes a buy and a sell simultaneously.
     *
     * This function is totally naive in that, if either leg of the buy/sell fails
     * it does not prevent the other leg from executing. It is entirely possible
     * that a buy can place as a sell fails, or vice-versa.
     *
     * Basic handling of logging when trade pairs are placed.
     *
     * @param Money::USD $bidPrice
     *   The bid price to place, in USD Money.
     *
     * @param Money::BTC $bidVolume
     *   The ask price to place, in BTC Money.
     *
     * @param Money::USD $askPrice
     *   The ask price to place, in USD Money.
     *
     * @param Money::BTC $askVolume
     *   The ask volume to place, in BTC Money.
     */
    public function execute(Money $bidPrice, Money $bidVolume, Money $askPrice, Money $askVolume)
    {
        try {
            $this->buy
                ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($bidPrice))
                ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($bidVolume))
                ->execute();
        } catch (\Exception $e) {
            // Even if the buy failed, we want to continue to the sell.
        }

        $this->sell
            ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($askPrice))
            ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($askVolume))
            ->execute();

        $this->logger->info('Trade pairs executed.');
    }

}
