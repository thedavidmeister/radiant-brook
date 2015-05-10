<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class CancelOrder extends PrivateBitstampAPI
{
    const ENDPOINT = 'cancel_order';

    public function requiredParams() 
    {
        return ['id'];
    }
}
