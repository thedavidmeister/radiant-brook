<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp sell API endpoint wrapper.
 */
class Sell extends PrivateBitstampAPI
{
    const ENDPOINT = 'sell';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['amount', 'price'];
    }
}
