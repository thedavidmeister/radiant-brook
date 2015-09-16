<?php

namespace AppBundle\API\Bitstamp\PublicAPI;

use AppBundle\API\Bitstamp\OrderList;

/**
 * Wraps a Bitstamp order book with some useful methods for data extraction.
 *
 * Returns JSON dictionary with "bids" and "asks". Each is a list of open orders and each order is represented as a list of price and amount.
 */
class OrderBook extends AbstractPublicAPI
{

    const ENDPOINT = 'order_book';

    protected $bidlist;

    protected $asklist;

    protected $logFullResponse = false;

    /**
     * Gets an OrderList for the order book bids.
     *
     * @return OrderList
     */
    public function bids()
    {
        if (!isset($this->bidlist)) {
            $this->bidlist = new OrderList($this->data()['bids']);
        }

        return $this->bidlist;
    }

    /**
     * Gets an OrderList for the order book asks.
     *
     * @return OrderList
     */
    public function asks()
    {
        if (!isset($this->asklist)) {
            $this->asklist = new OrderList($this->data()['asks']);
        }

        return $this->asklist;
    }

}
