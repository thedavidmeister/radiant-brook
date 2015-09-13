<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp buy private API endpoint wrapper.
 */
class Buy extends AbstractPrivateAPI
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
