<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp ripple withdrawal API endpoint wrapper.
 *
 * Returns true if successful.
 */
class RippleWithdrawal extends AbstractPrivateAPI
{
    const ENDPOINT = 'ripple_withdrawal';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['amount', 'address', 'currency'];
    }
}
