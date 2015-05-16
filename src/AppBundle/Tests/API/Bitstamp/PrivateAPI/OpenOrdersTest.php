<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp OpenOrders class.
 */
class OpenOrdersTest extends PrivateAPITest
{
    protected $endpoint = 'open_orders';
    protected $servicename = 'bitstamp.open_orders';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\OpenOrders';
}
