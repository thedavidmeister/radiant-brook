<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp order_status Private API endpoint wrapper.
 */
class OrderStatus extends PrivateAPI
{
    const ENDPOINT = 'order_status';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['id'];
    }
}
