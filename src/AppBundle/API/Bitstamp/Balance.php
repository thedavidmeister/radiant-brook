<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp balance private API endpoint wrapper.
 */
class Balance extends PrivateBitstampAPI
{
    const ENDPOINT = 'balance';
}
