<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class Balance extends PrivateBitstampAPI
{
  const ENDPOINT = 'balance';

  public function requiredParams() {
    return [];
  }

  // @todo - DEBUG ONLY!!!
  public function execute() {
    // @todo - check the format of "fee" with live data.
    return (array) json_decode('{"usd_balance": "100", "btc_balance": "10", "fee": "0.0025"}');
  }
}
