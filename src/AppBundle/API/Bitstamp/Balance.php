<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class Balance extends PrivateBitstampAPI
{
    const ENDPOINT = 'balance';

    public function requiredParams() 
    {
        return [];
    }

}
