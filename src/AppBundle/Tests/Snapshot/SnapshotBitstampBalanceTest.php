<?php

namespace AppBundle\Tests\Snapshot;

class SnapshotBitstampBalanceTest extends SnapshotBitstampTest
{
    protected $className = 'AppBundle\Snapshot\SnapshotBitstampBalance';
    protected $dataProviderClass = 'AppBundle\API\Bitstamp\PrivateAPI\Balance';
}
