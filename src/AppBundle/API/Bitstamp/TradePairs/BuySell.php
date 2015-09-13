<?php

namespace AppBundle\API\Bitstamp\TradePairs;

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
     * @param \AppBundle\API\Bitstamp\PrivateAPI\Buy  $buy
     * @param \AppBundle\API\Bitstamp\PrivateAPI\Sell $sell
     * @param \Psr\Log\LoggerInterface                $logger
     */
    public function __construct(
        \AppBundle\API\Bitstamp\PrivateAPI\Buy $buy,
        \AppBundle\API\Bitstamp\PrivateAPI\Sell $sell,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->buy = $buy;
        $this->sell = $sell;
        $this->logger = $logger;
    }

    const PRICE_KEY = 'price';

    const VOLUME_KEY = 'amount';

    protected function doBuy(Money $bidPrice, Money $bidVolume)
    {
        $this->buy
            ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($bidPrice))
            ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($bidVolume))
            ->execute();
    }

    protected function doSell(Money $askPrice, Money $askVolume)
    {
        $this->sell
            ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($askPrice))
            ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($askVolume))
            ->execute();
    }

    /**
     * Executes a buy and a sell simultaneously.
     *
     * This function is totally naive in that, if either leg of the buy/sell fails
     * it does not prevent the other leg from executing. It is entirely possible
     * that a buy can place as a sell fails, or vice-versa.
     *
     * Basic handling of logging when trade pairs are placed.
     *
     * @param TradeProposal $tradeProposal
     *   The proposal to execute on the market.
     */
    public function execute(TradeProposal $tradeProposal)
    {
        // Only execute valid TradeProposals.
        if (!$tradeProposal->isValid()) {
            throw new \Exception('Attempted to place invalid trade with reasons: ' . json_encode($tradeProposal->reasons()));
        }

        try {
            $this->doBuy($tradeProposal->bidUSDPrice(), $tradeProposal->bidBTCVolume());
        } catch (\Exception $e) {
            // Even if the buy failed, we want to continue to the sell.
        }

        $this->doSell($tradeProposal->askUSDPrice(), $tradeProposal->askBTCVolume());

        $this->logger->info('Trade pairs executed.');
    }

}
