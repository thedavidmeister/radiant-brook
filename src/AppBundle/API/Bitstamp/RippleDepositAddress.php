<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class RippleDepositAddress extends PrivateBitstampAPI
{
  const ENDPOINT = 'ripple_address';

  public function requiredParams() {
    return [];
  }
}
