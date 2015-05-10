<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;

/**
 * Bitstamp transactions API endpoint wrapper.
 */
class Transactions extends PublicBitstampAPI
{
    const ENDPOINT = 'transactions';
}
