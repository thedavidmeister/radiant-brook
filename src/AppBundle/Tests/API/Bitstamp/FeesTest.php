<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\Fees;

class FeesTest extends WebTestCase
{
  use GuzzleTestTrait;

  protected $sample = '{"btc_reserved":"0.10020833","fee":"0.2500","btc_available":"0.17213602","usd_reserved":"8.02","btc_balance":"0.27234435","usd_balance":"8.51","usd_available":"0.49"}';
  // Same as $sample but with 0.24 fees.
  protected $sample2 = '{"btc_reserved":"0.10020833","fee":"0.2400","btc_available":"0.17213602","usd_reserved":"8.02","btc_balance":"0.27234435","usd_balance":"8.51","usd_available":"0.49"}';

  protected function balance() {
    return new Balance($this->client(), $this->mockLogger(), $this->mockAuthenticator());
  }

  protected function fees() {
    return new Fees($this->balance());
  }

  protected function fees2() {
    $balance = $this->balance();
    $balance->execute();
    return new Fees($balance);
  }

  public function testPercent() {
    $this->assertSame(0.25, $this->fees()->percent());
    $this->assertSame(0.24, $this->fees2()->percent());
  }

  public function testMultiplier() {
    $this->assertSame(0.0025, $this->fees()->multiplier());
    $this->assertSame(0.0024, $this->fees2()->multiplier());
  }

}
