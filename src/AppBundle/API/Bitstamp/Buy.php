<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp buy private API endpoint wrapper.
 */
class Buy extends PrivateBitstampAPI
{
    // {@inheritdoc}
    const ENDPOINT = 'buy';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['amount', 'price'];
    }
}
