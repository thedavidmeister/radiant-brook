<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\MoneyStrings;

class BuySell
{
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

  public function execute(Money $bidPrice, Money $bidVolume, Money $askPrice, Money $askVolume) {
      $this->buy
      ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($bidPrice))
      ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($bidVolume))
      ->execute();

    $this->sell
      ->setParam(self::PRICE_KEY, MoneyStrings::USDToString($askPrice))
      ->setParam(self::VOLUME_KEY, MoneyStrings::BTCToString($askVolume))
      ->execute();

      $this->logger->info('Trade pairs executed.');
  }

}
