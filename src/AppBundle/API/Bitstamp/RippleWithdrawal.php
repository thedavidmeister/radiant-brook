<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class RippleWithdrawal extends PrivateBitstampAPI
{
  const ENDPOINT = 'ripple_withdrawal';

  public function requiredParams() {
    return ['amount', 'address', 'currency'];
  }
}
