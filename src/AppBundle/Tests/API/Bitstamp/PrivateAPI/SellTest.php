<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp Sell class.
 */
class SellTest extends PrivateAPITest
{
    protected $endpoint = 'sell';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    // If we accidentally place something live during testing, it won't resolve
    // in the market at 99999
    protected $requiredParamsFixture = ['amount' => 0.1, 'price' => 99999];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\Sell';
}
