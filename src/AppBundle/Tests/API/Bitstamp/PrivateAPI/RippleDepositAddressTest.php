<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp RippleDepositAddress class.
 */
class RippleDepositAddressTest extends PrivateAPITest
{
    protected $endpoint = 'ripple_address';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\RippleDepositAddress';
}
