<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

/**
 * Bitstamp cancel order Private API endpoint wrapper.
 */
class CancelOrder extends PrivateBitstampAPI
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
