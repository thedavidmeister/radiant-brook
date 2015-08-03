<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\API\Bitstamp\Dupes;
use AppBundle\Secrets;
use Money\Money;

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{
    protected $overriddenEnv = [];

    /**
     * Set environment variables in a way that we can clear them post-suite.
     *
     * If we set environment variables without tracking what we set, we cannot
     * clean them up later. If we cannot clean them up later, future usage of
     * Secrets will inherit our cruft and break future tests.
     *
     * @param string $key
     *   The key to set.
     * @param string $value
     *   The value to set.
     *
     * @see clearEnv()
     */
    protected function setEnv($key, $value)
    {
        $this->overriddenEnv[] = $key;
        $this->overriddenEnv = array_unique($this->overriddenEnv);

        $secrets = new Secrets();
        $secrets->set($key, $value);
    }

    protected function clearEnv($key)
    {
        $secrets = new Secrets();
        $secrets->clear($key);
    }

    protected function clearAllSetEnv()
    {
        array_walk($this->overriddenEnv, [$this, 'clearEnv']);
    }

    protected function tearDown()
    {
        $this->clearAllSetEnv();
    }

    protected function setIsTrading($isTrading)
    {
        $this->setEnv('BITSTAMP_IS_TRADING', $isTrading);
    }

    protected function setMinUSDVolume($volume)
    {
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $volume);
    }

    protected function setMinUSDProfit($min)
    {
        $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $min);
    }

    protected function setMinBTCProfit($min)
    {
        $this->setEnv('BITSTAMP_MIN_BTC_PROFIT', $min);
    }

    protected function setPercentile($percentile)
    {
        $this->setEnv('BITSTAMP_PERCENTILE', $percentile);
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

    protected function orderbook()
    {
        return $this->mock('\AppBundle\API\Bitstamp\PublicAPI\OrderBook');
    }

    protected function proposer()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\PriceProposer');
    }

    protected function tp()
    {
        return new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->proposer());
    }

    /**
     * Tests execution of trade pairs.
     */
    public function testExecute()
    {
        $this->tp()->execute();
    }

    /**
     * Data provider for testEnsureValid().
     *
     * @return array
     */
    public function dataEnsureValidExceptions()
    {
        return [
            ['Bitstamp trading is disabled at this time.', function() {
                $this->setIsTrading('false');
                $this->tp()->ensureValid();
            }],
        ];
    }

    /**
     * Tests ensureValid exceptions.
     *
     * @param string   $exceptionMessage
     *   The expected exception message.
     *
     * @param callable $trigger
     *   A function to trigger the exception.
     *
     * @dataProvider dataEnsureValidExceptions
     * @group stable
     */
    public function testEnsureValidExceptions($exceptionMessage, callable $trigger)
    {
        $this->setExpectedException('Exception', 'Invalid trade pairs: ' . $exceptionMessage);
        $trigger();
    }

    /**
     * Tests isTrading().
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
            [1, true],
            [true, true],
            // Other things.
            ['', false],
            [null, false],
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

    /**
     * Test volumeUSDAsk().
     *
     * @group stable
     */
    public function testVolumeUSDAsk()
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
            $this->setMinUSDProfit($test[2]);
            $fees->method('asksMultiplier')->willReturn($test[3]);

            $tp = new BitstampTradePairs($fees, $this->dupes(), $this->buysell(), $this->orderbook());
            $this->assertEquals($test[4], $tp->volumeUSDAsk());
        });
    }

    /**
     * Test volumeUSDBidPostFees().
     *
     * @group stable
     */
    public function testVolumeUSDBidPostFees()
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

            $tp = new BitstampTradePairs($fees, $this->dupes(), $this->buysell(), $this->orderbook());
            $this->assertEquals($test[2], $tp->volumeUSDBidPostFees());
        });
    }

    /**
     * Test baseVolumeUSDBid() and volumeUSDBid().
     *
     * @group stable
     */
    public function testVolumeUSDBid()
    {
        $fees = $this->fees();
        // Set a value for isofeeMaxUSD to return because we need to check it
        // when testing volumeUSDBid().
        $fees->method('isofeeMaxUSD')->willReturn(Money::USD(1230));

        // Check that the min USD volume can be read from config.
        $tp = new BitstampTradePairs($fees, $this->dupes(), $this->buysell(), $this->orderbook());
        foreach ([123, 234] as $test) {
            $this->setMinUSDVolume($test);
            $this->assertEquals(Money::USD($test), $tp->baseVolumeUSDBid());
        }

        // Check that volumeUSDBid() is interacting with isofee correctly.
        $this->assertEquals(Money::USD(1230), $tp->volumeUSDBid());
    }

    /**
     * Test askPrice().
     *
     * @group stable
     *
     * @return null
     */
    public function testAskPrice()
    {
        // percentile.
        $tests = [
            [0.05],
            [0.01],
            [0.5],
            [1],
            [0],
        ];
        array_walk($tests, function($test) {
            $orderbook = $this->orderbook();
            $orderbook->method('asks')->will($this->returnCallback(function() {
                $asks = $this
                    ->getMockBuilder('AppBundle\API\Bitstamp\OrderList')
                    ->disableOriginalConstructor()
                    ->getMock();

                $asks->method('percentileCap')->will($this->returnCallback(function($percentile) {
                    return (int) $percentile * 12345678;
                }));

                return $asks;
            }));

            $expected = Money::USD((int) $test[0] * 12345678);
            $this->setPercentile($test[0]);
            $tp = new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $orderbook);

            $this->assertEquals($expected, $tp->askPrice());
        });
    }

    /**
     * Test bidPrice().
     *
     * @group stable
     *
     * @return null
     */
    public function testBidPrice()
    {
        // percentile.
        $tests = [
            [0.05],
            [0.01],
            [0.5],
            [1],
            [0],
        ];
        array_walk($tests, function($test) {
            // This mocking gets deep...
            $orderbook = $this->orderbook();
            $orderbook->method('bids')->will($this->returnCallback(function() {
                $bids = $this
                    ->getMockBuilder('AppBundle\API\Bitstamp\OrderList')
                    ->disableOriginalConstructor()
                    ->getMock();

                $bids->method('percentileCap')->will($this->returnCallback(function($percentile) {
                    return (int) $percentile * 1000000;
                }));

                return $bids;
            }));
            // bidPrice() passes (1 - $percentile) to percentileCap().
            $expected = Money::USD((int) (1 - $test[0]) * 1000000);

            $this->setPercentile($test[0]);
            $tp = new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $orderbook);

            $this->assertEquals($expected, $tp->bidPrice());
        });
    }

    /**
     * Test minProfitUSD().
     *
     * @group stable
     */
    public function testMinProfitUSD()
    {
        $tp = $this->tp();
        // sets, expects.
        $tests = [
            [0, Money::USD(0)],
            ['0', Money::USD(0)],
            ['1', Money::USD(1)],
            ['100', Money::USD(100)],
            [1, Money::USD(1)],
        ];
        foreach ($tests as $test) {
            $this->setMinUSDProfit($test[0]);
            $this->assertEquals($test[1], $tp->minProfitUSD());
        }
    }

    /**
     * Data provider for testMinProfitUSDExceptions().
     *
     * @return array
     */
    public function dataMinProfitUSDExceptions()
    {
        // sets, expects.
        return [
            ['foo'],
            [1.5],
            ['1.0'],
            ['1.99'],
        ];
    }

    /**
     * Test minProfitUSD Exceptions.
     *
     * @param mixed $sets
     *   Invalid minimum profit configuration that should throw an exception.
     *
     * @dataProvider dataMinProfitUSDExceptions
     * @expectedException Exception
     * @expectedExceptionMessage Minimum USD profit configuration must be an integer value.
     *
     * @group stable
     */
    public function testMinProfitUSDExceptions($sets)
    {
        $tp = $this->tp();
        $this->setMinUSDProfit($sets);
        $tp->minProfitUSD();
    }

    /**
     * Test min profit BTC.
     *
     * @group stable
     */
    public function testMinProfitBTC()
    {
        $tp = $this->tp();
        // sets, expects.
        $tests = [
            [0, Money::BTC(0)],
            ['0', Money::BTC(0)],
            ['1', Money::BTC(1)],
            ['100', Money::BTC(100)],
            [1, Money::BTC(1)],
        ];
        foreach ($tests as $test) {
            $this->setMinBTCProfit($test[0]);
            $this->assertEquals($test[1], $tp->minProfitBTC());
        }
    }

    /**
     * Data provider for testMinProfitBTCExceptions().
     *
     * @return array
     */
    public function dataMinProfitBTCExceptions()
    {
        // sets, expects.
        return [
            ['foo'],
            [1.5],
            ['1.0'],
            ['1.99'],
        ];
    }

    /**
     * Test minProfitBTC Exceptions.
     *
     * @param mixed $sets
     *   Invalid minimum profit configuration that should throw an exception.
     *
     * @dataProvider dataMinProfitUSDExceptions
     * @expectedException Exception
     * @expectedExceptionMessage Minimum BTC profit configuration must be an integer value.
     *
     * @group stable
     */
    public function testMinProfitBTCExceptions($sets)
    {
        $tp = $this->tp();
        $this->setMinBTCProfit($sets);
        $tp->minProfitBTC();
    }
}
