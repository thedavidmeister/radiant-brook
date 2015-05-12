<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPI;

/**
 * Bitstamp sell API endpoint wrapper.
 */
class Sell extends PrivateAPI
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
