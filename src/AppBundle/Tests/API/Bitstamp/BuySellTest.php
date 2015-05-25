<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\PrivateAPI\Buy;
use AppBundle\API\Bitstamp\PrivateAPI\Sell;
use AppBundle\API\Bitstamp\BuySell;
use AppBundle\Tests\GuzzleTestTrait;
use Money\Money;

/**
 * Tests AppBundle\API\Bitstamp\BuySell
 */
class BuySellTest extends WebTestCase
{

    use GuzzleTestTrait;

    // @todo - replace with real data sample.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';

    protected function buy()
    {
        return new Buy($this->client(), $this->mockLogger(), $this->mockAuthenticator());
    }

    protected function sell()
    {
        return new Sell($this->client(), $this->mockLogger(), $this->mockAuthenticator());
    }

    protected function buySell()
    {
        return new BuySell($this->buy(), $this->sell(), $this->mockLogger());
    }

    /**
     * Tests execute() to ensure the right values are being sent to Bitstamp.
     */
    public function testExecute()
    {
        // We shouldn't have to do too much here as all the internals are being
        // tested elsewhere (although we can't totally trust that of course).
        // bidPrice, bidVolume, askPrice, askVolume.
        $tests = [
            [Money::USD(1), Money::BTC(1), Money::USD(1), Money::BTC(1), '0.01', '0.00000001', '0.01', '0.00000001'],
            [Money::USD(1), Money::BTC(1), Money::USD(2), Money::BTC(2), '0.01', '0.00000001', '0.02', '0.00000002'],
            [Money::USD(123), Money::BTC(123), Money::USD(20), Money::BTC(20), '1.23', '0.00000123', '0.20', '0.00000020'],
        ];

        foreach ($tests as $test) {
            $buySell = $this->buySell();
            $buySell->execute($test[0], $test[1], $test[2], $test[3]);
            $buyRequest = $buySell->buy->client->history->getLastRequest();
            $sellRequest = $buySell->sell->client->history->getLastRequest();

            $this->assertSame($test[4], $buyRequest->getBody()->getField('price'));
            $this->assertSame($test[5], $buyRequest->getBody()->getField('amount'));

            $this->assertSame($test[6], $sellRequest->getBody()->getField('price'));
            $this->assertSame($test[7], $sellRequest->getBody()->getField('amount'));
        }
    }
}
