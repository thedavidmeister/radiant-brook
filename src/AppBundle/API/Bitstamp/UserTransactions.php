<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp user transactions API endpoint wrapper.
 */
class UserTransactions extends PrivateBitstampAPI
{
    const ENDPOINT = 'user_transactions';
}
