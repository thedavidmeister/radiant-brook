<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp balance private API endpoint wrapper.
 *
 * This API call is cached for 10 seconds.
 */
class Balance extends AbstractPrivateAPI
{
    const ENDPOINT = 'balance';
}
