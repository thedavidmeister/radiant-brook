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

    const PERCENTILE_CAP_MULTIPLIER_ASKS = 12345678;

    const PERCENTILE_CAP_MULTIPLIER_BIDS = 10000000;

    protected function orderbook()
    {
        $orderbook = $this->mock('\AppBundle\API\Bitstamp\PublicAPI\OrderBook');

        $orderbook->method('asks')->will($this->returnCallback(function() {
            $asks = $this
                ->getMockBuilder('AppBundle\API\Bitstamp\OrderList')
                ->disableOriginalConstructor()
                ->getMock();

            $asks->method('percentileCap')->will($this->returnCallback(function($percentile) {
                return (int) ($percentile * self::PERCENTILE_CAP_MULTIPLIER_ASKS);
            }));

            return $asks;
        }));

        $orderbook->method('bids')->will($this->returnCallback(function() {
            $bids = $this
                ->getMockBuilder('AppBundle\API\Bitstamp\OrderList')
                ->disableOriginalConstructor()
                ->getMock();

            $bids->method('percentileCap')->will($this->returnCallback(function($percentile) {
                return (int) ($percentile * self::PERCENTILE_CAP_MULTIPLIER_BIDS);
            }));

            return $bids;
        }));

        return $orderbook;
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::current
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::key
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::next
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::rewind
     */
    public function testIteration()
    {
        $minPercentile = 0.01;
        $this->setEnv('BITSTAMP_PERCENTILE_MIN', $minPercentile);

        $maxPercentile = 0.1;
        $this->setEnv('BITSTAMP_PERCENTILE_MAX', $maxPercentile);

        $stepSize = 0.005;
        $this->setEnv('BITSTAMP_PERCENTILE_STEP', $stepSize);

        $pp = new PriceProposer($this->orderbook());
        $currentPercentile = $minPercentile;
        foreach ($pp as $key => $value) {
            // Test key().
            $this->assertSame($currentPercentile, $key);

            // Test current().
            $expectedValue = [
                'bidUSDPrice' => Money::USD((int) ((1 - $key) * self::PERCENTILE_CAP_MULTIPLIER_BIDS)),
                'askUSDPrice' => Money::USD((int) ($key * self::PERCENTILE_CAP_MULTIPLIER_ASKS)),
            ];
            $this->assertEquals($expectedValue, $value);

            // This is what happens inside next() so we are implicitly testing
            // next by using foreach and tracking against the step size here.
            $currentPercentile += $stepSize;
        }

        // Test rewind.
        $pp->rewind();
        $this->assertSame($minPercentile, $pp->key());
        $expectedValue = [
            'bidUSDPrice' => Money::USD((int) ((1 - $pp->key()) * self::PERCENTILE_CAP_MULTIPLIER_BIDS)),
            'askUSDPrice' => Money::USD((int) ($pp->key() * self::PERCENTILE_CAP_MULTIPLIER_ASKS)),
        ];
        $this->assertEquals($expectedValue, $pp->current());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::__construct
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::minPercentile
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::maxPercentile
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::stepSize
     *
     * @group stable
     */
    public function testInternals()
    {
        $orderbook = $this->orderbook();

        $minPercentile = 0.05;
        $this->setEnv('BITSTAMP_PERCENTILE_MIN', $minPercentile);

        $maxPercentile = 0.1;
        $this->setEnv('BITSTAMP_PERCENTILE_MAX', $maxPercentile);

        $stepSize = 0.005;
        $this->setEnv('BITSTAMP_PERCENTILE_STEP', $stepSize);

        $pp = new PriceProposer($orderbook);
        $this->assertSame($minPercentile, $pp->minPercentile());
        $this->assertSame($maxPercentile, $pp->maxPercentile());
        $this->assertSame($stepSize, $pp->stepSize());
        $this->assertSame($minPercentile, $pp->key());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::askUSDPrice
     *
     * @group stable
     *
     * @return null
     */
    public function testAskUSDPrice()
    {
        // percentile.
        $tests = [
            ['0.05'],
            ['0.01'],
            ['0.5'],
            ['1'],
            ['0'],
        ];
        array_walk($tests, function($test) {
            $orderbook = $this->orderbook();

            $expected = Money::USD((int) $test[0] * 12345678);

            // New PriceProposers start at BITSTAMP_PERCENTILE_MIN.
            $this->setEnv('BITSTAMP_PERCENTILE_MIN', $test[0]);
            // Exceptions without a max.
            $this->setEnv('BITSTAMP_PERCENTILE_MAX', $test[0] + 0.1);
            $this->setEnv('BITSTAMP_PERCENTILE_STEP', $test[0] + 0.01);

            $pp = new PriceProposer($orderbook);

            $this->assertEquals($expected, $pp->askUSDPrice(), 'Testing percentile ' . $test[0]);

            $this->clearEnv('BITSTAMP_PERCENTILE_MIN');
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::bidUSDPrice
     *
     * @group stable
     *
     * @return null
     */
    public function testBidUSDPrice()
    {
        // percentile.
        $tests = [
            ['0.05'],
            ['0.01'],
            ['0.5'],
            ['1'],
            ['0'],
        ];
        array_walk($tests, function($test) {
            // This mocking gets deep...
            $orderbook = $this->orderbook();

            // bidPrice() passes (1 - $percentile) to percentileCap().
            $expected = Money::USD((int) ((1 - $test[0]) * self::PERCENTILE_CAP_MULTIPLIER_BIDS));

            // New PriceProposers start at BITSTAMP_PERCENTILE_MIN.
            $this->setEnv('BITSTAMP_PERCENTILE_MIN', $test[0]);
            // Exceptions without these set.
            $this->setEnv('BITSTAMP_PERCENTILE_MAX', $test[0] + 0.1);
            $this->setEnv('BITSTAMP_PERCENTILE_STEP', $test[0] + 0.01);

            $pp = new PriceProposer($orderbook);

            $this->assertEquals($expected, $pp->bidUSDPrice(), 'Testing percentile ' . $test[0]);
        });
    }
}
