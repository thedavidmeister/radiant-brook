<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp ripple deposit address API endpoint wrapper.
 */
class RippleDepositAddress extends PrivateBitstampAPI
{
    const ENDPOINT = 'ripple_address';
}
