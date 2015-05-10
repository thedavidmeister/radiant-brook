<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp ripple withdrawal API endpoint wrapper.
 */
class RippleWithdrawal extends PrivateBitstampAPI
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
