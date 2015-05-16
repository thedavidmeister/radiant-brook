<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp EURUSD class.
 */
class BalanceTest extends PrivateAPITest
{
    protected $endpoint = 'balance';
    protected $servicename = 'bitstamp.balance';
    protected $sample = '{"btc_reserved": "0.03280538", "fee": "0.2500", "btc_available": "0.17162632", "usd_reserved": "16.04", "btc_balance": "0.20443170", "usd_balance": "24.49", "usd_available": "8.45"}';
    protected $sample2 = '{"btc_reserved": "0.03280538", "fee": "0.1500", "btc_available": "0.17162632", "usd_reserved": "16.04", "btc_balance": "0.20443170", "usd_balance": "24.49", "usd_available": "8.45"}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\Balance';
}
