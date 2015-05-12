<?php

namespace AppBundle\API\Bitstamp\PublicAPI;

use AppBundle\API\Bitstamp\OrderList;

/**
 * Wraps a Bitstamp order book with some useful methods for data extraction.
 */
class OrderBook extends PublicAPI
{

    const ENDPOINT = 'order_book';

    protected $bidlist;

    protected $asklist;

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
