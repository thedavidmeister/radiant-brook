<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp RippleDepositAddress class.
 */
class RippleWithdrawalTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'ripple_withdrawal';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\RippleWithdrawal';
    protected $requiredParamsFixture = ['amount' => 0, 'address' => 'not a real address', 'currency' => 'not a currency'];
}
