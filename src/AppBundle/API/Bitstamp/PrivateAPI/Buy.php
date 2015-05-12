<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPI;

/**
 * Bitstamp buy private API endpoint wrapper.
 */
class Buy extends PrivateAPI
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
