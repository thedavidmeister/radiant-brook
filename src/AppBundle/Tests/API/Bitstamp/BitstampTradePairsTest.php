<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\BitstampTradePairs;
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
        return $this->mock('\AppBundle\API\Bitstamp\Fees');
    }

    protected function dupes()
    {
        return $this->mock('\AppBundle\API\Bitstamp\Dupes');
    }

    protected function buysell()
    {
        return $this->mock('\AppBundle\API\Bitstamp\BuySell');
    }

    protected function orderbook()
    {
        return $this->mock('\AppBundle\API\Bitstamp\PublicAPI\OrderBook');
    }

    protected function tp()
    {
        return new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->orderbook());
    }

    /**
     * Test volumeUSDBidPostFees().
     *
     * @group stable
     */
    public function testVolumeUSDBidPostFees() {
        // The USD bid volume post fees is equal to the max isofee USD volume
        // plus the absolute value of USD fees.
        $fees = $this->fees();
        $fees->method('absoluteFeeUSD')->willReturn(Money::USD(2340));
        $fees->method('isofeeMaxUSD')->willReturn(Money::USD(3450));

        $tp = new BitstampTradePairs($fees, $this->dupes(), $this->buysell(), $this->orderbook());
        $this->assertEquals(Money::USD(5790), $tp->volumeUSDBidPostFees());
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
     * Test bidPrice().
     *
     * @group stable
     *
     * @return null
     */
    public function testBidPrice()
    {
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

        $tp = new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $orderbook);

        foreach ([0.05, 0.01, 0.5] as $percentile) {
            $this->assertEquals(Money::USD((int) $percentile * 1000000), $tp->bidPrice());
        }
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
