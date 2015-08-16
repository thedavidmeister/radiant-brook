<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs;
use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\Tests\EnvironmentTestTrait;
use AppBundle\API\Bitstamp\Dupes;
use AppBundle\Secrets;
use Money\Money;
use Prophecy\Prophet;
use Prophecy\Argument;

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{

    use EnvironmentTestTrait;

    protected function setup()
    {
        $this->prophet = new Prophet();
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    protected function setIsTrading($isTrading)
    {
        $this->setEnv('BITSTAMP_IS_TRADING', $isTrading);
    }

    protected function setMinUSDVolume($volume)
    {
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $volume);
    }

    protected function mock($class)
    {
        return $this
            ->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function fees()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Fees');
    }

    protected function dupes()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Dupes');
    }

    protected function buysell()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\BuySell');
    }

    protected function proposer()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\PriceProposer');
    }

    protected function tp()
    {
        return new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->proposer());
    }

    protected function tradeProposal()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::validateTradeProposal
     *
     * group stable
     */
    public function testValidateTradeProposal()
    {
        // isProfitable, dupeBids, dupeAsks, invalidateReason, panicReason
        $tests = [
            [true, [], [], null, null],
            [false, [], [], 'Not a profitable trade proposition.', null],
            [true, [uniqid()], [], null, 'Duplicate trade pairs found.'],
            [true, [], [uniqid()], null, 'Duplicate trade pairs found.'],
            [true, [uniqid()], [uniqid()], null, 'Duplicate trade pairs found.'],
            [false, [uniqid()], [], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
            [false, [], [uniqid()], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
            [false, [uniqid()], [uniqid()], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
        ];

        array_walk($tests, function($test) {
            $tradeProposalProphet = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');

            // We expect isProfitable() to be called, to check for profit
            // invalidation.
            $tradeProposalProphet->isProfitable()->willReturn($test[0])->shouldBeCalled();

            // We only expect invalidate to be called if the trade is not
            // profitable.
            if (isset($test[3])) {
                $tradeProposalProphet->invalidate($test[3])->shouldBeCalled();
            } else {
                $tradeProposalProphet->invalidate(Argument::any())->shouldNotBeCalled();
            }

            // We expect the bid and ask USD prices to be called in the search
            // for dupes.
            $priceReturn = Money::USD(mt_rand());
            $tradeProposalProphet->bidUSDPrice()->willReturn($priceReturn)->shouldBeCalled();
            $tradeProposalProphet->askUSDPrice()->willReturn($priceReturn)->shouldBeCalled();

            $dupesProphet = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\Dupes');
            $dupesProphet->bids($priceReturn)->willReturn($test[1])->shouldBeCalled();
            $dupesProphet->asks($priceReturn)->willReturn($test[2])->shouldBeCalled();

            // We only expect panic to be called if there is a dupe.
            if (isset($test[4])) {
                $tradeProposalProphet->panic($test[4])->shouldBeCalled();
            } else {
                $tradeProposalProphet->panic(Argument::any())->shouldNotBeCalled();
            }

            // We expect validate() to be called unconditionally as it is always
            // overridden by a higher state anyway.
            $tradeProposalProphet->validate()->shouldBeCalled();

            // Attempt validation.
            $tp = new BitstampTradePairs($this->fees(), $dupesProphet->reveal(), $this->buysell(), $this->proposer());
            $tp->validateTradeProposal($tradeProposalProphet->reveal());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::isTrading
     *
     * @group stable
     */
    public function testIsTrading()
    {
        // env value, expected.
        $tests = [
            // Truthy strings.
            ['true', true],
            ['TRUE', true],
            ['1', true],
            ['yes', true],
            // Other strings.
            ['', false],
            ['no', false],
            ['one', false],
            ['foo', false],
            ['false', false],
            ['FALSE', false],
            // filter_var() doesn't recognise y/n.
            ['y', false],
            ['n', false],
        ];
        array_walk($tests, function($test) {
            $this->setIsTrading($test[0]);
            $this->assertEquals($test[1], $this->tp()->isTrading());
        });
    }
}
