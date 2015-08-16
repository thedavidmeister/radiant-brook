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

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{

    use EnvironmentTestTrait;

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

    public function testValidateTradeProposal()
    {
        // isProfitable, hasDupes, invalidateReason, panicReason
        $tests = [
            [true, false, null, null],
            [false, false, 'Not a profitable trade proposition.', null],
            [false, true, 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
        ];

        array_walk($tests, function($test) {
            $tradeProposal = $this->tradeProposal();
            $tradeProposal->method('isProfitable')->willReturn($test[0]);

            $tradeProposal->expects($spyIsProfitable = $this->any())->method('isProfitable');
            $tradeProposal->expects($spyInvalidate = $this->any())->method('invalidate');
            $tradeProposal->expects($spyPanic = $this->any())->method('panic');

            // Annoyingly, we cannot spy on calls to methods on $dupes once it
            // is passed to BitstampTradePairs.
            $dupes = $this->dupes();
            $dupes->method('tradeProposalHasDupes')->willReturn($test[1]);

            $tp = new BitstampTradePairs($this->fees(), $dupes, $this->buysell(), $this->proposer());

            print_r('foo: ');
            print $tp->dupes->tradeProposalHasDupes($tradeProposal);

        \Psy\Shell::debug(get_defined_vars(), $this);
            $this->tp()->validateTradeProposal($tradeProposal);

            $this->assertSame(1, count($spyIsProfitable->getInvocations()));

            $spyInvalidateInvocations = $spyInvalidate->getInvocations();
            if (isset($test[2])) {
                $this->assertSame(1, count($spyInvalidateInvocations));
                $this->assertSame($test[2], end($spyInvalidateInvocations)->parameters[0]);
            } else {
                $this->assertSame(0, count($spyInvalidateInvocations));
            }

            $spyPanicInvocations = $spyPanic->getInvocations();
            if (isset($test[3])) {
                $this->assertSame(1, count($spyPanicInvocations));
                $this->assertSame($test[3], end($spyPanicInvocations)->parameters[0]);
            } else {
                $this->assertSame(0, count($spyPanicInvocations));
            }
        });

    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::validateTradeProposal
     */
    // public function testValidateTradeProposalProfitable()
    // {
    //     $tradeProposal = $this->tradeProposal();
    //     $tradeProposal->method('isProfitable')->willReturn(true);

    //     // A profitable TradeProposal must not have invalidate called.
    //     // Profit is not a panic test.
    //     $tradeProposal->expects($spyIsProfitable = $this->any())->method('isProfitable');
    //     $tradeProposal->expects($spyInvalidate = $this->any())->method('invalidate');
    //     $tradeProposal->expects($spyPanic = $this->any())->method('panic');
    //     $validatedProposal = $this->tp()->validateTradeProposal($tradeProposal);
    //     $this->assertSame(1, count($spyIsProfitable->getInvocations()));
    //     $this->assertSame(0, count($spyInvalidate->getInvocations()));
    //     $this->assertSame(0, count($spyPanic->getInvocations()));
    // }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::validateTradeProposal
     */
    public function testValidateTradeProposalNotProfitable()
    {
        // $tradeProposal = $this->tradeProposal();
        // $tradeProposal->method('isProfitable')->willReturn(false);

        // // An unprofitable TradeProposal must have invalidate called.
        // // Profit is not a panic test.
        // $tradeProposal->expects($spyIsProfitable = $this->any())->method('isProfitable');
        // $tradeProposal->expects($spyInvalidate = $this->any())->method('invalidate');
        // $tradeProposal->expects($spyPanic = $this->any())->method('panic');
        // $this->tp()->validateTradeProposal($tradeProposal);
        // $this->assertSame(1, count($spyIsProfitable->getInvocations()));

        // $spyInvalidateInvocations = $spyInvalidate->getInvocations();
        // $this->assertSame(1, count($spyInvalidateInvocations));
        // $this->assertSame('Not a profitable trade proposition.', end($spyInvalidateInvocations)->parameters[0]);
        // $this->assertSame(0, count($spyPanic->getInvocations()));
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
