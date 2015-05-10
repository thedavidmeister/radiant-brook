<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class BitcoinDepositAddress extends PrivateBitstampAPI
{
    const ENDPOINT = 'bitcoin_deposit_address';

    public function requiredParams() 
    {
        return [];
    }
}
