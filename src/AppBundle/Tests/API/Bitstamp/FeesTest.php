<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\Fees;
use Money\Money;

/**
 * Tests AppBundle\API\Bitstamp\Fees
 */
class FeesTest extends WebTestCase
{
    use GuzzleTestTrait;

    protected $sample = '{"btc_reserved":"0.10020833","fee":"0.2500","btc_available":"0.17213602","usd_reserved":"8.02","btc_balance":"0.27234435","usd_balance":"8.51","usd_available":"0.49"}';
    // Same as $sample but with 0.24 fees.
    protected $sample2 = '{"btc_reserved":"0.10020833","fee":"0.2400","btc_available":"0.17213602","usd_reserved":"8.02","btc_balance":"0.27234435","usd_balance":"8.51","usd_available":"0.49"}';

    protected function balance()
    {
        return new Balance($this->client(), $this->mockLogger(), $this->mockAuthenticator());
    }

    protected function fees()
    {
        return new Fees($this->balance());
    }

    protected function fees2()
    {
        $balance = $this->balance();
        $balance->execute();

        return new Fees($balance);
    }


    /**
     * Tests percent().
     *
     * @group stable
     */
    public function testPercent()
    {
        $this->assertSame(0.25, $this->fees()->percent());
        $this->assertSame(0.24, $this->fees2()->percent());
    }

    /**
     * Tests multiplier().
     *
     * @group stable
     */
    public function testMultiplier()
    {
        $this->assertSame(0.0025, $this->fees()->bidsMultiplier());
        $this->assertSame(0.9975, $this->fees()->asksMultiplier());
        $this->assertSame(0.0024, $this->fees2()->bidsMultiplier());
        $this->assertSame(0.9976, $this->fees2()->asksMultiplier());
    }

    /**
     * Tests absoluteFeeUSD().
     *
     * @group stable
     */
    public function testAbsoluteFeeUSD()
    {
        $tests = [
            [200, 1, 1],
            [250, 1, 1],
            [300, 1, 1],
            [350, 1, 1],
            [400, 1, 1],
            [450, 2, 2],
            [500, 2, 2],
            [550, 2, 2],
            [600, 2, 2],
            [650, 2, 2],
            [700, 2, 2],
            [750, 2, 2],
            [800, 2, 2],
            [850, 3, 3],
            [900, 3, 3],
            [950, 3, 3],
            [1000, 3, 3],
            [1050, 3, 3],
            [1100, 3, 3],
            [1150, 3, 3],
            [1200, 3, 3],
            // This is the first point the difference in fees kicks in.
            [1250, 4, 3],
        ];
        foreach ($tests as $test) {
            $this->assertEquals(Money::USD($test[1]), $this->fees()->absoluteFeeUSD(Money::USD($test[0])));
            $this->assertEquals(Money::USD($test[2]), $this->fees2()->absoluteFeeUSD(Money::USD($test[0])));
        }
    }

    /**
     * Tests absoluteFeeUSD exceptions.
     *
     * @expectedException Exception
     * @expectedExceptionMessage Cannot calculate fees for negative amounts
     * @group stable
     */
    public function testAbsoluteFeeUSDExceptions()
    {
        $this->fees()->absoluteFeeUSD(Money::USD(-1));
    }

    /**
     * Tests the calculation of the Max USD on the isofee.
     */
    public function testIsofeeMaxUSD()
    {
        $tests = [
          [200, 400, 416],
          [250, 400, 416],
          [300, 400, 416],
          [350, 400, 416],
          [400, 400, 416],
          [450, 800, 833],
          [500, 800, 833],
          [550, 800, 833],
          [600, 800, 833],
          [650, 800, 833],
          [700, 800, 833],
          [750, 800, 833],
          [800, 800, 833],
          [850, 1200, 1250],
          [900, 1200 ,1250],
          [950, 1200, 1250],
          [1000, 1200, 1250],
          [1050, 1200, 1250],
          [1100, 1200, 1250],
          [1150, 1200, 1250],
          [1200, 1200, 1250],
          [1250, 1600, 1250],
        ];
        foreach ($tests as $test) {
            $this->assertEquals(Money::USD($test[1]), $this->fees()->isofeeMaxUSD(Money::USD($test[0])));
            $this->assertEquals(Money::USD($test[2]), $this->fees2()->isofeeMaxUSD(Money::USD($test[0])));
        }
    }

}
