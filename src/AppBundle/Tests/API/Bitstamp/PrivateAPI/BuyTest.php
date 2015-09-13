<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp Sell class.
 */
class BuyTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'buy';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    // If we accidentally place something live during testing, it won't resolve
    // in the market at 0.001.
    protected $requiredParamsFixture = ['amount' => 0.1, 'price' => 0.001];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\Buy';
}
