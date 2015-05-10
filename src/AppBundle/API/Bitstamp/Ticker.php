<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;

/**
 * Bitstamp ticker API endpoint wrapper.
 */
class Ticker extends PublicBitstampAPI
{
    const ENDPOINT = 'ticker';
}
