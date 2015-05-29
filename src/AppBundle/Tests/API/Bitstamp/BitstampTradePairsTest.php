<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\BitstampTradePairs;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\API\Bitstamp\Dupes;
use Money\Money;

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{
    protected function mock($class) {
      return $this
        ->getMockBuilder($class)
        ->disableOriginalConstructor()
        ->getMock();
    }

    protected function fees() {
      return $this->mock('\AppBundle\API\Bitstamp\Fees');
    }

    protected function dupes() {
      return $this->mock('\AppBundle\API\Bitstamp\Dupes');
    }

    protected function orderbook() {
      return $this->mock('\AppBundle\API\Bitstamp\PublicAPI\OrderBook');
    }

    protected function buysell() {
      return $this->mock('\AppBundle\API\Bitstamp\BuySell');
    }

    protected function setMinUSDVolume($volume) {
      putenv('BITSTAMP_MIN_USD_VOLUME=' . $volume);
    }

    protected function setPercentile($percentile) {
      putenv('BITSTAMP_PERCENTILE=' . $percentile);
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
      foreach([123, 234] as $test) {
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
}
