<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp bitcoin deposit address private API endpoint wrapper.
 */
class BitcoinDepositAddress extends PrivateBitstampAPI
{
    const ENDPOINT = 'bitcoin_deposit_address';
}
