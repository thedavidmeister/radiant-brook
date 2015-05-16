<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp BitcoinDepositAddress class.
 */
class BitcoinDepositAddressTest extends PrivateAPITest
{
    protected $endpoint = 'bitcoin_deposit_address';
    protected $servicename = 'bitstamp.bitcoin_deposit_address';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\BitcoinDepositAddress';
}
