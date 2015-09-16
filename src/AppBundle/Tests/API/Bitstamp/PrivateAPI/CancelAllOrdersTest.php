<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp CancelAllOrders class.
 */
class CancelAllOrdersTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'cancel_all_orders';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    // If we accidentally place something live during testing, it won't match
    // any id.
    protected $requiredParamsFixture = ['id' => 'match nothing'];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\CancelAllOrders';
}
