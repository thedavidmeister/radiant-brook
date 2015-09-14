<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\MoneyStringsUtil;
use Money\Money;

/**
 * Convenience class to lodge a buy and sell pair simultaneously.
 */
class BuySell
{
    protected $buy;

    protected $sell;

    protected $logger;

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
            ->setParam(self::PRICE_KEY, MoneyStringsUtil::USDToString($bidPrice))
            ->setParam(self::VOLUME_KEY, MoneyStringsUtil::BTCToString($bidVolume))
            ->execute();
    }

    protected function doSell(Money $askPrice, Money $askVolume)
    {
        $this->sell
            ->setParam(self::PRICE_KEY, MoneyStringsUtil::USDToString($askPrice))
            ->setParam(self::VOLUME_KEY, MoneyStringsUtil::BTCToString($askVolume))
            ->execute();
    }

    /**
     * Read-only access to the protected $buy property.
     *
     * @return \AppBundle\API\Bitstamp\PrivateAPI\Buy
     */
    public function buy()
    {
        return $this->buy;
    }

    /**
     * Read-only access to the protected $sell property.
     *
     * @return \AppBundle\API\Bitstamp\PrivateAPI\Sell
     */
    public function sell()
    {
        return $this->sell;
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
