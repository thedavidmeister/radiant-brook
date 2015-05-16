<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp bitcoin withdrawal private API endpoint wrapper.
 */
class BitcoinWithdrawal extends PrivateAPI
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
