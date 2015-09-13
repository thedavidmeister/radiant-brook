<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp cancel order Private API endpoint wrapper.
 *
 * Returns 'true' if order has been found and canceled.
 */
class CancelOrder extends AbstractPrivateAPI
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
