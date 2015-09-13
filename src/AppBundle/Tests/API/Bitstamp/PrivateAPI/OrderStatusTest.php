<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp OrderStatus class.
 */
class OrderStatusTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'order_status';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\OrderStatus';
    protected $requiredParamsFixture = ['id' => 'not a real id'];
}
