<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class WithdrawalRequests extends PrivateBitstampAPI
{
    const ENDPOINT = 'withdrawal_requests';

    public function requiredParams() 
    {
        return [];
    }
}
