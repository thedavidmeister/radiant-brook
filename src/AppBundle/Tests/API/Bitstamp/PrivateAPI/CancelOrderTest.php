<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp CancelOrder class.
 */
class CancelOrderTest extends PrivateAPITest
{
    protected $endpoint = 'cancel_order';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    // If we accidentally place something live during testing, it won't match
    // any id.
    protected $requiredParamsFixture = ['id' => 'match nothing'];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\CancelOrder';
}
