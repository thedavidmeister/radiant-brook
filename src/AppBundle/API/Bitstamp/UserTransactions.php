<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class UserTransactions extends PrivateBitstampAPI
{
    const ENDPOINT = 'user_transactions';

    public function requiredParams() 
    {
        return [];
    }
}
