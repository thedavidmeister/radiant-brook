<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp ripple deposit address API endpoint wrapper.
 *
 * Returns your ripple deposit address.
 */
class RippleDepositAddress extends PrivateAPI
{
    const ENDPOINT = 'ripple_address';
}
