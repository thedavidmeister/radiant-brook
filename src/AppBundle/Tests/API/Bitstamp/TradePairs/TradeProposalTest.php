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
     * @covers TradeProposal::bidUSDPrice
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
     * @covers TradeProposal::bidUSDVolumeBase
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
     * @covers TradeProposal::bidUSDVolume
     *
     * @group stable
     *
     * @return void
     */
    public function testBidUSDVolume()
    {
        // Check that the isofee is returned.
        $fees = $this->fees();
        $isoFeeAmount = mt_rand();
        $fees->method('isofeeMaxUSD')->willReturn(Money::USD($isoFeeAmount));
        $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);
        $this->assertEquals(Money::USD($isoFeeAmount), $tradeProposal->bidUSDVolume());

        // Check that the volume for a known isofee is returned. We mock
        // isofee to just return 2x the base value.
        $tests = [
            [5, 10],
            [10, 20],
        ];
        array_walk($tests, function($test) {
            $fees = $this->fees();
            $fees->method('isofeeMaxUSD')->will($this->returnCallback(function(Money $usd) {
                return $usd->multiply(2);
            }));

            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $test[0]);
            $this->assertEquals(Money::USD($test[1]), $tradeProposal->bidUSDVolume());
        });
    }

    /**
     * @covers TradeProposal::bidUSDVolumePlusFees
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
            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);

            // USD bid volume has nothing to do with prices.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);

            $this->assertEquals($test[2], $tradeProposal->bidUSDVolumePlusFees());
        });
    }

    /**
     * @covers TradeProposal::minProfitBTC
     *
     * @group stable
     */
    public function testMinProfitBTC()
    {
        // sets, expects.
        $scenarios = [
        [0, Money::BTC(0)],
        ['0', Money::BTC(0)],
        ['1', Money::BTC(1)],
        ['100', Money::BTC(100)],
        [1, Money::BTC(1)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_BTC_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposal()->minProfitBTC());
        };
        array_walk($scenarios, $test);
    }

    /**
     * @covers TradeProposal::askUSDVolumeCoverFees
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
        [Money::USD(0), Money::USD(0), 0, 1, Money::USD(0)],
        [Money::USD(1), Money::USD(1), 1, 3, Money::USD(1)],
        [Money::USD(2), Money::USD(2), 2, 3, Money::USD(2)],
        // Flush out failures to handle min USD profit setting.
        [Money::USD(100), Money::USD(200), 300, 0.5, Money::USD(1200)],
        // Test for something where ceiling will matter.
        [Money::USD(123), Money::USD(234), 345, 0.456, Money::USD(1540)],
        ];

        array_walk($tests, function($test) {
            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $test[2]);
            $fees->method('asksMultiplier')->willReturn($test[3]);

            // The USD volume has nothing to do with the price.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees);

            $this->assertEquals($test[4], $tradeProposal->askUSDVolumeCoverFees());
        });
    }

    /**
     * @covers TradeProposalTest::minProfitUSD
     *
     * @group stable
     */
    public function testMinProfitUSD()
    {
        // sets, expects.
        $scenarios = [
          [0, Money::USD(0)],
          ['0', Money::USD(0)],
          ['1', Money::USD(1)],
          ['100', Money::USD(100)],
          [1, Money::USD(1)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposal()->minProfitUSD());
        };
        array_walk($scenarios, $test);
    }
}
