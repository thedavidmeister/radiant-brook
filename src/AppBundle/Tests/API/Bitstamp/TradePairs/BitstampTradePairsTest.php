<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs;
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
}
