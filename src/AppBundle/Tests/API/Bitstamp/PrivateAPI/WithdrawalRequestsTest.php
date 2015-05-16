<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp UnconfirmedBitcoinDeposits class.
 */
class WithdrawalRequestsTest extends PrivateAPITest
{
    protected $endpoint = 'withdrawal_requests';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\WithdrawalRequests';
}
