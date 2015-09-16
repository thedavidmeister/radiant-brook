<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use AppBundle\API\Bitstamp\PrivateAPI\Buy;
use AppBundle\API\Bitstamp\PrivateAPI\Sell;
use AppBundle\API\Bitstamp\TradePairs\BuySell;
use AppBundle\Tests\GuzzleTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\BuySell
 */
class BuySellTest extends WebTestCase
{
    protected $prophet;

    protected function sample()
    {
        return '{"foo": "bar"}';
    }

    protected function sample2()
    {
        return '{"bing": "baz"}';
    }

    use GuzzleTestTrait;

    protected function setup()
    {
        $this->prophet = new \Prophecy\Prophet;
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

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

    protected function tradeProposal(Money $bidUSDPrice, Money $bidBTCVolume, Money $askUSDPrice, Money $askBTCVolume)
    {
        $tradeProposal = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');

        $tradeProposal->bidUSDPrice()->willReturn($bidUSDPrice)->shouldBeCalled();
        $tradeProposal->bidBTCVolume()->willReturn($bidBTCVolume)->shouldBeCalled();
        $tradeProposal->askUSDPrice()->willReturn($askUSDPrice)->shouldBeCalled();
        $tradeProposal->askBTCVolume()->willReturn($askBTCVolume)->shouldBeCalled();

        return $tradeProposal;
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::buy
     *
     * @group stable
     */
    public function testBuy()
    {
        $buy = $this->buy();
        $buySell = new BuySell($buy, $this->sell(), $this->mockLogger());

        $this->assertSame($buy, $buySell->buy());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::sell
     *
     * @group stable
     */
    public function testSell()
    {
        $sell = $this->sell();
        $buySell = new BuySell($this->buy(), $sell, $this->mockLogger());

        $this->assertSame($sell, $buySell->sell());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::execute
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::doBuy
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::doSell
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::__construct
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

        array_walk($tests, function($test) {
            $buySell = $this->buySell();

            $tradeProposal = $this->tradeProposal($test[0], $test[1], $test[2], $test[3]);
            $tradeProposal->isValid()->willReturn(true)->shouldBeCalled();

            $buySell->execute($tradeProposal->reveal());
            $buyRequest = $buySell->buy()->client->history->getLastRequest();
            $sellRequest = $buySell->sell()->client->history->getLastRequest();

            $this->assertSame($test[4], $buyRequest->getBody()->getField('price'));
            $this->assertSame($test[5], $buyRequest->getBody()->getField('amount'));

            $this->assertSame($test[6], $sellRequest->getBody()->getField('price'));
            $this->assertSame($test[7], $sellRequest->getBody()->getField('amount'));
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BuySell::execute
     *
     * @group stable
     */
    public function testExecuteInvalidTradeProposalException()
    {
        $tradeProposal = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');

        // Any state other than 0 is a fail. We expect buySell to ask for a
        // state and reason when generating the exception.
        $reasons = [uniqid(), uniqid()];
        $tradeProposal->isValid()->willReturn(false)->shouldBeCalled();
        $tradeProposal->reasons()->willReturn($reasons)->shouldBeCalled();

        $tradeProposal->bidUSDPrice()->shouldNotBeCalled();
        $tradeProposal->bidBTCVolume()->shouldNotBeCalled();
        $tradeProposal->askUSDPrice()->shouldNotBeCalled();
        $tradeProposal->askBTCVolume()->shouldNotBeCalled();

        $this->setExpectedException('Exception', 'Attempted to place invalid trade with reasons: ' . json_encode($reasons));
        $this->buySell()->execute($tradeProposal->reveal());
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

        $tradeProposal = $this->tradeProposal(Money::USD(1), Money::BTC(1), Money::USD(1), Money::BTC(1));
        $tradeProposal->isValid()->willReturn(true)->shouldBeCalled();

        $buyfail->execute($tradeProposal->reveal());

        $this->assertNotEmpty($buyfail->buy()->client->history->getLastRequest());
        $this->assertNotEmpty($buyfail->sell()->client->history->getLastRequest());
    }
}
