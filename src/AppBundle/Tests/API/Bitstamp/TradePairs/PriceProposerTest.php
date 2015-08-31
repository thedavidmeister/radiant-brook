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
     * Data provider for testMinMaxStepExceptions
     *
     * @return array
     */
    public function dataMinMaxStepExceptions()
    {
        return [
            // Anything null is an exception.
            [[null, null, null]],
            [[1, null, null]],
            [[null, 1, null]],
            [[null, null, 1]],
            [[1, 1, null]],
            [[null, 1, 1]],
            [[1, null, 1]],
            // This will throw because min is not less than max.
            [[1, 1, 1]],
        ];
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::__construct
     *
     * @dataProvider dataMinMaxStepExceptions
     *
     * @param array $minMaxStep
     *   The minMaxStep array to pass to PriceProposer.
     *
     * @group stable
     */
    public function testMinMaxStepExceptions($minMaxStep)
    {
        $this->setExpectedException('Exception');

        new PriceProposer($this->orderbook(), $minMaxStep);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::valid
     *
     * @group stable
     */
    public function testValid()
    {
        $minMaxStep = [0.0, 0.2, 0.1];

        list($minPercentile, $maxPercentile, $stepSize) = $minMaxStep;

        $pp = new PriceProposer($this->orderbook(), $minMaxStep);

        // $pp should be valid at the start.
        $this->assertTrue($pp->valid());
        $this->assertSame($minPercentile, $pp->key());

        // $pp should be valid after one step, so key advances by step.
        $pp->next();
        $this->assertSame($minPercentile + $stepSize, $pp->key());
        $this->assertTrue($pp->valid());

        // $pp is still valid after two steps, so key advances by step.
        $pp->next();
        $this->assertSame($minPercentile + $stepSize + $stepSize, $pp->key());
        $this->assertTrue($pp->valid());

        // $pp is not valid after three steps, so key wraps to start.
        $pp->next();
        $this->assertSame($minPercentile + $stepSize + $stepSize + $stepSize, $pp->key());
        $this->assertFalse($pp->valid());

        // $pp is valid once more after a rewind.
        $pp->rewind();
        $this->assertSame($minPercentile, $pp->key());
        $this->assertTrue($pp->valid());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::current
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::key
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::next
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::rewind
     *
     * @group stable
     */
    public function testIteration()
    {
        $minMaxStep = [0.01, 0.1, 0.005];

        list($minPercentile, $maxPercentile, $stepSize) = $minMaxStep;

        $pp = new PriceProposer($this->orderbook(), $minMaxStep);

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

        $minMaxStep = [0.05, 0.1, 0.005];

        list($minPercentile, $maxPercentile, $stepSize) = $minMaxStep;

        $pp = new PriceProposer($orderbook, $minMaxStep);

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

        // Fill in some max and steps.
        $tests = array_map(function ($item) {
            return [$item[0], $item[0] + 0.1, $item[0.01]];
        }, $tests);

        array_walk($tests, function($test) {
            $orderbook = $this->orderbook();

            $expected = Money::USD((int) ($test[0] * 12345678));

            $pp = new PriceProposer($orderbook, $test);

            $this->assertEquals($expected, $pp->askUSDPrice(), 'Testing percentile ' . $test[0]);
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

        // Fill in some max and steps.
        $tests = array_map(function ($item) {
            return [$item[0], $item[0] + 0.1, $item[0.01]];
        }, $tests);

        array_walk($tests, function($test) {
            // This mocking gets deep...
            $orderbook = $this->orderbook();

            // bidPrice() passes (1 - $percentile) to percentileCap().
            $expected = Money::USD((int) ((1 - $test[0]) * self::PERCENTILE_CAP_MULTIPLIER_BIDS));

            $pp = new PriceProposer($orderbook, $test);

            $this->assertEquals($expected, $pp->bidUSDPrice(), 'Testing percentile ' . $test[0]);
        });
    }
}
