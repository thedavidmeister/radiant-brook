<?php

namespace AppBundle\Snapshot;

use AppBundle\API\Bitstamp\PrivateAPI\Balance;

class SnapshotBitstampBalance extends SnapshotBitstamp
{
    const EVENT_NAME = 'bitstamp_balance';

    public function __construct(Balance $balance)
    {
        $this->dataProvider = $balance;
    }
}
