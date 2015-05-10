<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp bitcoin withdrawal private API endpoint wrapper.
 */
class BitcoinWithdrawal extends PrivateBitstampAPI
{
    const ENDPOINT = 'bitcoin_withdrawal';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['amount', 'address'];
    }
}
