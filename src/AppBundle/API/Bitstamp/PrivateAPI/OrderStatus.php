<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp order_status Private API endpoint wrapper.
 */
class OrderStatus extends AbstractPrivateAPI
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
