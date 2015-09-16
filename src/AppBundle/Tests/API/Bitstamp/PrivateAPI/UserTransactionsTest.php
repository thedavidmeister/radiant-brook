<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp UserTransactions class.
 */
class UserTransactionTest extends AbstractPrivateAPITest
{
    protected $endpoint = 'user_transactions';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\UserTransactions';
}
