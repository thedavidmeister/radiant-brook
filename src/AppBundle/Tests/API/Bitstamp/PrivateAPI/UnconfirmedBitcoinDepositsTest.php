<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp UnconfirmedBitcoinDeposits class.
 */
class UnconfirmedBitcoinDepositsTest extends PrivateAPITest
{
    protected $endpoint = 'unconfirmed_btc';
    protected $servicename = 'bitstamp.unconfirmed_btc';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\UnconfirmedBitcoinDeposits';
}
