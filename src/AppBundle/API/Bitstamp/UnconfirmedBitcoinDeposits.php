<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Unconfirmed Bitcoin Deposits Bitstamp API endpoint wrapper.
 */
class UnconfirmedBitcoinDeposits extends PrivateBitstampAPI
{
    const ENDPOINT = 'unconfirmed_btc';
}
