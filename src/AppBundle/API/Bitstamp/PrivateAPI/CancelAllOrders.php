<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp cancel order Private API endpoint wrapper.
 *
 * This call will cancel all open orders.
 *
 * Returns 'true' if all orders have been canceled, false if it failed.
 */
class CancelAllOrders extends PrivateAPI
{
    const ENDPOINT = 'cancel_all_orders';
}
