<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp witdrawal requests API wrapper.
 */
class WithdrawalRequests extends PrivateBitstampAPI
{
    const ENDPOINT = 'withdrawal_requests';

}
