<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\PrivateAPI\Buy;
use AppBundle\API\Bitstamp\PrivateAPI\Sell;
use AppBundle\API\Bitstamp\TradePairs\BuySell;
use Money\Money;
use AppBundle\Tests\GuzzleTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\BuySell
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
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::execute
     *
     * @group stable
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

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::execute
     *
     * @group stable
     */
    public function testExecuteErrors()
    {
        $client = new Client();
        $client->history = new History();

        // Bitstamp returns us an error that looks like this if we have no USD
        // left.
        $buyfail = new Mock([
            new Response(200, [], Stream::factory('{"error":{"__all__":["You need $8.02 to open that order. You have only $0.55 available. Check your account balance for details."]}}')),
        ]);

        // Add the mock subscriber to the client.
        $client->getEmitter()->attach($buyfail);
        $client->getEmitter()->attach($client->history);

        $buyfail = new BuySell(new Buy($client, $this->mockLogger(), $this->mockAuthenticator()), $this->sell(), $this->mockLogger());
        $buyfail->execute(Money::USD(1), Money::BTC(1), Money::USD(1), Money::BTC(1));

        $this->assertNotEmpty($buyfail->buy->client->history->getLastRequest());
        $this->assertNotEmpty($buyfail->sell->client->history->getLastRequest());
    }
}
