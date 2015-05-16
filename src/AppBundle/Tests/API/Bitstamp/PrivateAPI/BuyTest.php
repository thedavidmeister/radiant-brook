<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp EURUSD class.
 */
class BuyTest extends PrivateAPITest
{
    protected $endpoint = 'buy';
    protected $servicename = 'bitstamp.buy';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $requiredParamsFixture = ['amount' => 0.1, 'price' => 0.1];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\Buy';
}
