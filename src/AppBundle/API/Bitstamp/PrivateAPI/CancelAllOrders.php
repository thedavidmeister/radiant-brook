<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp cancel all orders Private API endpoint wrapper.
 *
 * This call will cancel all open orders.
 *
 * Returns 'true' if all orders have been canceled, false if it failed.
 */
class CancelAllOrders extends AbstractPrivateAPI
{
    const ENDPOINT = 'cancel_all_orders';
}
