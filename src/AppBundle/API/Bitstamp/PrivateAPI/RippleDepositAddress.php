<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp ripple deposit address API endpoint wrapper.
 *
 * Returns your ripple deposit address.
 */
class RippleDepositAddress extends AbstractPrivateAPI
{
    const ENDPOINT = 'ripple_address';
}
