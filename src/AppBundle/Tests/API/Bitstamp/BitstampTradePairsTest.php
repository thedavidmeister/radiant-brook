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

    protected function setMinUSDVolume($volume) {
      putenv('BITSTAMP_MIN_USD_VOLUME=' . $volume);
    }

    /**
     * Fill me out.
     *
     * @group stable
     */
    public function testVolumeUSDBid()
    {
      $fees = $this
        ->getMockBuilder('\AppBundle\API\Bitstamp\Fees')
        ->disableOriginalConstructor()
        ->getMock();

      $fees
        // Set a value for isofeeMaxUSD to return because we need to check it
        // when testing volumeUSDBid().
        ->method('isofeeMaxUSD')->willReturn(Money::USD(1230));

      $dupes = $this
        ->getMockBuilder('\AppBundle\API\Bitstamp\Dupes')
        ->disableOriginalConstructor()
        ->getMock();

      $orderbook = $this
        ->getMockBuilder('\AppBundle\API\Bitstamp\PublicAPI\OrderBook')
        ->disableOriginalConstructor()
        ->getMock();

      $buysell = $this
        ->getMockBuilder('\AppBundle\API\Bitstamp\BuySell')
        ->disableOriginalConstructor()
        ->getMock();

      // Check that the min USD volume can be read from config.
      $tp = new BitstampTradePairs($fees, $dupes, $buysell, $orderbook);
      foreach([123, 234] as $test) {
        $this->setMinUSDVolume($test);
        $this->assertEquals(Money::USD($test), $tp->baseVolumeUSDBid());
      }

      // Check that volumeUSDBid() is interacting with isofee correctly.
      $this->assertEquals(Money::USD(1230), $tp->volumeUSDBid());
    }
}
