<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPI;

/**
 * Bitstamp balance private API endpoint wrapper.
 *
 * This API call is cached for 10 seconds.
 */
class Balance extends PrivateAPI
{
    const ENDPOINT = 'balance';
}
