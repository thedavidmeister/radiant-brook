<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp OrderStatus class.
 */
class OrderStatusTest extends PrivateAPITest
{
    protected $endpoint = 'order_status';
    protected $servicename = 'bitstamp.order_status';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\OrderStatus';
    protected $requiredParamsFixture = ['id' => 'not a real id'];
}
