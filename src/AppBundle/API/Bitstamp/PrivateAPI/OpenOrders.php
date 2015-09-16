<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp open orders private API endpoint wrapper.
 *
 * This API call is cached for 10 seconds.
 */
class OpenOrders extends AbstractPrivateAPI
{
    // {@inheritdoc}
    const ENDPOINT = 'open_orders';
}
