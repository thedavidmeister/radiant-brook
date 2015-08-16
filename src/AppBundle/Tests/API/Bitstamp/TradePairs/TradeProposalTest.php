<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use Money\Money;
use AppBundle\Tests\EnvironmentTestTrait;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\TradeProposal.
 */
class TradeProposalTest extends WebTestCase
{
    use EnvironmentTestTrait;

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

    protected function tradeProposal()
    {
        return new TradeProposal($this->randomBidAskPrices(), $this->fees());
    }

    protected function randomBidAskPrices()
    {
        return ['bidUSDPrice' => Money::USD(mt_rand()), 'askUSDPrice' => Money::USD(mt_rand())];
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::state
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::reason
     *
     * @group stable
     */
    public function testStateReason()
    {
        // Test that a brand new TradeProposal is in the valid state/reason.
        // These methods are read-only from the public API.
        $tp = $this->tradeProposal();
        $tp->validate();
        $this->assertSame(TradeProposal::STATE_VALID, $tp->state());
        $this->assertSame(0, $tp->state());
        $this->assertSame(TradeProposal::STATE_VALID_REASON, $tp->reason());
        $this->assertSame('Valid trade pair.', $tp->reason());
    }

    /**
     * Tests exception thrown when state is not set.
     *
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::state
     */
    public function testStateNotSetException()
    {
      $tp = $this->tradeProposal();
      $this->setExpectedException('Exception', 'No state has been set for this TradeProposal, it has not been validated correctly.');
      $tp->state();
    }

    /**
     * Tests exception thrown when reason is not set.
     *
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::reason
     */
    public function testStateReasonNotSetException()
    {
      $tp = $this->tradeProposal();
      $this->setExpectedException('Exception', 'No state reason has been set for this TradeProposal, it has not been validated correctly.');
      $tp->reason();
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::invalidate
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::setState
     *
     * @group stable
     */
    public function testStateInvalidate()
    {
        $tp = $this->tradeProposal();
        $reason = uniqid();
        $tp->invalidate($reason);
        // Validate should not do anything after an invalidate.
        $tp->validate();
        $this->assertSame(TradeProposal::STATE_INVALID, $tp->state());
        $this->assertSame(1, $tp->state());
        $this->assertSame($reason, $tp->reason());

        // Need an exception if we invalidate with no good reason.
        $this->setExpectedException('Exception');
        $tp->invalidate('');
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::panic
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::setState
     *
     * @group stable
     */
    public function testStatePanic()
    {
        // Test moving through invalid to panic.
        $tp = $this->tradeProposal();
        $invalidateReason = uniqid();
        $panicReason = uniqid();
        $tp->invalidate($invalidateReason);
        $tp->panic($panicReason);
        // Validate should not do anything at this point.
        $tp->validate();

        $this->assertSame(TradeProposal::STATE_PANIC, $tp->state());
        $this->assertSame(2, $tp->state());
        $this->assertSame($panicReason, $tp->reason());

        // We cannot move backwards.
        $anotherInvalidateReason = uniqid();
        $tp->invalidate($anotherInvalidateReason);

        $this->assertSame(TradeProposal::STATE_PANIC, $tp->state());
        $this->assertSame(2, $tp->state());
        $this->assertSame($panicReason, $tp->reason());

        // Jump straight to panic from a valid state.
        $tp2 = $this->tradeProposal();
        $panicReason2 = uniqid();
        $tp2->panic($panicReason2);
        // Validate should not do anything at this point.
        $tp2->validate();

        $this->assertSame(TradeProposal::STATE_PANIC, $tp2->state());
        $this->assertSame(2, $tp2->state());
        $this->assertSame($panicReason2, $tp2->reason());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDPrice
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::__construct
     *
     * @group stable
     */
    public function testBidUSDPrice()
    {
        $prices = $this->randomBidAskPrices();
        $tradeProposal = new TradeProposal($prices, $this->fees());
        $this->assertEquals($prices['bidUSDPrice'], $tradeProposal->bidUSDPrice());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolumeBase
     *
     * @group stable
     */
    public function testBidUSDVolumeBase()
    {
        // Check basic functionality.
        $minVolume = mt_rand();
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $minVolume);
        $this->assertEquals(Money::USD($minVolume), $this->tradeProposal()->bidUSDVolumeBase());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolume
     *
     * @group stable
     *
     * @return void
     */
    public function testBidUSDVolume()
    {
        // Check that the isofee is returned.
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
        $fees = $this->fees();
        $isoFeeAmount = mt_rand();
        $fees->method('isofeeMaxUSD')->willReturn(Money::USD($isoFeeAmount));
        $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);
        $this->assertEquals(Money::USD($isoFeeAmount), $tradeProposal->bidUSDVolume());

        // Check that the volume for a known isofee is returned. We mock
        // isofee to just return 2x the base value.
        $tests = [
            ['5', 10],
            ['10', 20],
        ];
        array_walk($tests, function($test) {
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $test[0]);

            $fees = $this->fees();
            $fees->method('isofeeMaxUSD')->will($this->returnCallback(function(Money $usd) {
                return $usd->multiply(2);
            }));

            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);
            $this->assertEquals(Money::USD($test[1]), $tradeProposal->bidUSDVolume());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolumePlusFees
     *
     * @group stable
     */
    public function testBidUSDVolumePlusFees()
    {
        // The USD bid volume post fees is equal to the max isofee USD volume
        // plus the absolute value of USD fees.
        // absoluteFeeUSD, isofeeMaxUSD, expected.
        $tests = [
        [Money::USD(2340), Money::USD(3450), Money::USD(5790)],
        [Money::USD(0), Money::USD(0), Money::USD(0)],
        [Money::USD(1), Money::USD(0), Money::USD(1)],
        [Money::USD(0), Money::USD(1), Money::USD(1)],
        [Money::USD(-1), Money::USD(0), Money::USD(-1)],
        [Money::USD(0), Money::USD(-1), Money::USD(-1)],
        ];

        array_walk($tests, function($test) {
            // This can be anything.
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());

            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);

            // USD bid volume has nothing to do with prices.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);

            $this->assertEquals($test[2], $tradeProposal->bidUSDVolumePlusFees());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidBTCVolume
     *
     * @group stable
     */
    public function testBidBTCVolume()
    {
        // bidUSDVolume amount, bidUSDPrice amount, expected satoshis amount.
        // The amounts are converted to Money in the test for convenience.
        $tests = [
            // Basic tests.
            [100, 50, 2 * 10 ** 8],
            [2, 1, 2 * 10 ** 8],
            [1, 2, 5 * 10 ** 7],
            // 999 / 100000000 * 10 ** 8 = 999.0 => will floor to 998
            [999, 100000000, 998],
            // 99 / 100000000 * 10 ** 8 = 99.0 => will floor to 99
            [999, 1000000000, 99],
            [111, 100000000, 111],
            [111, 1000000000, 11],
        ];
        array_walk($tests, function($test) {
            // This can be set to anything here.
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
            $fees = $this->fees();
            $fees->method('isofeeMaxUSD')->willReturn(Money::USD($test[0]));
            $prices = ['bidUSDPrice' => Money::USD($test[1]), 'askUSDPrice' => Money::USD(mt_rand())];

            $tradeProposal = new TradeProposal($prices, $fees);
            $this->assertEquals(Money::BTC($test[2]), $tradeProposal->bidBTCVolume());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::askUSDPrice
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::__construct
     *
     * @group stable
     */
    public function testAskUSDPrice()
    {
        $askUSDPrice = Money::USD(mt_rand());
        $prices = [
            'bidUSDPrice' => Money::USD(mt_rand()),
            'askUSDPrice' => $askUSDPrice,
        ];
        $tp = new TradeProposal($prices, $this->fees());
        $this->assertEquals($askUSDPrice, $tp->askUSDPrice());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::askUSDVolumeCoverFees
     *
     * @group stable
     */
    public function testAskUSDVolumeCoverFees()
    {
        // The USD ask volume required to cover the bid volume, desired USD
        // profit and all fees is:
        // (bidUSDPostFees + minUSDProfit) / feeAskMultiplier
        // => (absoluteFeeUSD + isofeeMaxUSD + minProfitUSD) / feeAskMultiplier
        // We get the ceiling of this to be absolutely sure we've covered
        // everything.
        // @see volumeUSDAsk() documentation.
        // absoluteFeeUSD, isofeeMaxUSD, minProfitUSD, feeAskMultiplier,
        // expected.
        $tests = [
        // Simple cases.
        [Money::USD(0), Money::USD(0), '0', 1, Money::USD(0)],
        [Money::USD(1), Money::USD(1), '1', 3, Money::USD(1)],
        [Money::USD(2), Money::USD(2), '2', 3, Money::USD(2)],
        // Flush out failures to handle min USD profit setting.
        [Money::USD(100), Money::USD(200), '300', 0.5, Money::USD(1200)],
        // Test for something where ceiling will matter.
        [Money::USD(123), Money::USD(234), '345', 0.456, Money::USD(1540)],
        ];

        array_walk($tests, function($test) {
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $test[2]);

            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);
            $fees->method('asksMultiplier')->willReturn($test[3]);

            // The USD volume has nothing to do with the price.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);

            $this->assertEquals($test[4], $tradeProposal->askUSDVolumeCoverFees());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::minProfitBTC
     *
     * @group stable
     */
    public function testMinProfitBTC()
    {
        // sets, expects.
        $scenarios = [
            ['0', Money::BTC(0)],
            ['1', Money::BTC(1)],
            ['100', Money::BTC(100)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_BTC_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposal()->minProfitBTC());
        };
        array_walk($scenarios, $test);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::minProfitUSD
     *
     * @group stable
     */
    public function testMinProfitUSD()
    {
        // sets, expects.
        $scenarios = [
          ['0', Money::USD(0)],
          ['1', Money::USD(1)],
          ['10', Money::USD(10)],
          ['100', Money::USD(100)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposal()->minProfitUSD());
        };
        array_walk($scenarios, $test);
    }
}
