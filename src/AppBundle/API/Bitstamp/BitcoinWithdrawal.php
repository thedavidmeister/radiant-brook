<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class BitcoinWithdrawal extends PrivateBitstampAPI
{
    const ENDPOINT = 'bitcoin_withdrawal';

    public function requiredParams() 
    {
        return ['amount', 'address'];
    }
}
