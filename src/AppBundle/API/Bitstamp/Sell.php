<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class Sell extends PrivateBitstampAPI
{
  const ENDPOINT = 'sell';

  public function requiredParams() {
    return ['amount', 'price'];
  }
}
