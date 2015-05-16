<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp cancel order Private API endpoint wrapper.
 */
class CancelOrder extends PrivateAPI
{
    const ENDPOINT = 'cancel_order';

    /**
     * {@inheritdoc}
     */
    public function requiredParams()
    {
        return ['id'];
    }
}
