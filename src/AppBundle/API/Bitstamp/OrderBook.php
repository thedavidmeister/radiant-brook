<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;
use AppBundle\API\Bitstamp\OrderList;

class OrderBook extends PublicBitstampAPI
{

    const ENDPOINT = 'order_book';

    protected $bidlist;

    protected $asklist;

    public function bids()
    {
        if (!isset($this->bidlist)) {
            $this->bidlist = new OrderList($this->data()['bids']);
        }

        return $this->bidlist;
    }

    public function asks()
    {
        if (!isset($this->asklist)) {
            $this->asklist = new OrderList($this->data()['asks']);
        }

        return $this->asklist;
    }

}
