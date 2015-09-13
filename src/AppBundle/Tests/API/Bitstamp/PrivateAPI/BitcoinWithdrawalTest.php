<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp Sell class.
 */
class BitcoinWithdrawalTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'bitcoin_withdrawal';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    // If we accidentally place something live during testing, it won't go
    // anywhere.
    protected $requiredParamsFixture = ['amount' => 0, 'address' => 'not a real address'];
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\BitcoinWithdrawal';
}
