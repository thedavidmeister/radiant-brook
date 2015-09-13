<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp sell API endpoint wrapper.
 */
class Sell extends AbstractPrivateAPI
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
