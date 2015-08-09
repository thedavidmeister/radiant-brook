<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\TradePairs\PriceProposer;
use AppBundle\Tests\EnvironmentTestTrait;
use Money\Money;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\PriceProposer
 */
class PriceProposerTest extends WebTestCase
{

    use EnvironmentTestTrait;

    protected function mock($class)
    {
        return $this
            ->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function orderbook()
    {
        return $this->mock('\AppBundle\API\Bitstamp\PublicAPI\OrderBook');
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::askUSDPrice
     *
     * @return null
     */
    public function testAskPrice()
    {
        // percentile.
        $tests = [
            ['0.05'],
            ['0.01'],
            ['0.5'],
            // ['1'],
            ['0'],
        ];
        array_walk($tests, function($test) {
            $orderbook = $this->orderbook();
            $orderbook->method('asks')->will($this->returnCallback(function() {
                $asks = $this
                    ->getMockBuilder('AppBundle\API\Bitstamp\OrderList')
                    ->disableOriginalConstructor()
                    ->getMock();

                $asks->method('percentileCap')->will($this->returnCallback(function($percentile) {
                  // print_r($percentile);
                    return (int) $percentile * 12345678;
                }));

                return $asks;
            }));

            $expected = Money::USD((int) $test[0] * 12345678);

            // New PriceProposers start at BITSTAMP_PERCENTILE_MIN.
            $this->setEnv('BITSTAMP_PERCENTILE_MIN', $test[0]);

            $pp = new PriceProposer($orderbook);

            $this->assertEquals($expected, $pp->askUSDPrice(), 'Testing percentile ' . $test[0]);

            $this->clearEnv('BITSTAMP_PERCENTILE_MIN');
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::bidUSDPrice
     *
     * group stable
     *
     * @return null
     */
    public function testBidPrice()
    {
        // percentile.
        $tests = [
            // ['0.05'],
            // ['0.01'],
            // ['0.5'],
            // ['1'],
            // ['0'],
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

            // New PriceProposers start at BITSTAMP_PERCENTILE_MIN.
            $this->setEnv('BITSTAMP_PERCENTILE_MIN', $test[0]);

            $pp = new PriceProposer($orderbook);

            $this->assertEquals($expected, $pp->bidUSDPrice(), 'Testing percentile ' . $test[0]);

            $this->clearEnv('BITSTAMP_PERCENTILE_MIN');
        });
    }
}
