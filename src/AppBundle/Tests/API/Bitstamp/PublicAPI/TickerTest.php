<?php

namespace AppBundle\Tests\API\Bitstamp\PublicAPI;

/**
 * Tests the Bitstamp Ticker class.
 */
class TickerTest extends PublicAPITest
{
    protected $endpoint = 'ticker';
    protected $sample = '{"high": "242.90", "last": "240.83", "timestamp": "1431351913", "bid": "240.55", "vwap": "239.57", "volume": "6435.83679504", "low": "237.99", "ask": "240.83"}';
    protected $sample2 = '{"high": "242.60", "last": "241.85", "timestamp": "1431353704", "bid": "240.97", "vwap": "239.59", "volume": "6517.73015869", "low": "237.99", "ask": "241.25"}';
    protected $className = 'AppBundle\API\Bitstamp\PublicAPI\Ticker';
}
