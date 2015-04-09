<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class Buy extends PrivateBitstampAPI
{
  const ENDPOINT = 'buy';

  public function requiredParams() {
    return ['amount', 'price', 'limit_price'];
  }
}
