<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class UnconfirmedBitcoinDeposits extends PrivateBitstampAPI
{
  const ENDPOINT = 'unconfirmed_btc';

  public function requiredParams() {
    return [];
  }
}
