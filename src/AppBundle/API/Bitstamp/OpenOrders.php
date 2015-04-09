<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class OpenOrders extends PrivateBitstampAPI
{
  const ENDPOINT = 'open_orders';

  public function requiredParams() {
    return [];
  }
}
