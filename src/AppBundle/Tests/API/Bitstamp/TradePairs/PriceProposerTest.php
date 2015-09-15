<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use AppBundle\API\Bitstamp\TradePairs\PriceProposer;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\PriceProposer
 */
class PriceProposerTest extends WebTestCase
{

    /**
     * @param string $class
     *
     * @return mixed
     */
    protected function mock($class)
    {
        return $this
            ->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    const PERCENTILE_CAP_MULTIPLIER_ASKS = 12345678;

    const PERCENTILE_CAP_MULTIPLIER_BIDS = 10000000;

    /**
     * @return OrderBook
     */
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
     * @return array<array|string>
     */
    public function dataMinMaxStepExceptions()
    {
        return [
            // Anything null is an exception.
            [[null, null, null], 'null must be a finite number'],
            [['1', null, null], 'null must be a finite number'],
            [[null, '1', null], 'null must be a finite number'],
            [[null, null, '1'], 'null must be a finite number'],
            [['1', '1', null], 'null must be a finite number'],
            [[null, '1', '1'], 'null must be a finite number'],
            [['1', null, '1'], 'null must be a finite number'],
            // This will throw because min is not less than max.
            [['1', '1', '0.5'], '"1" must be greater than "1"'],
            // Step size must be less than max - min.
            [['1', '2', '3'], '"3" must be lower than or equals "1"'],
            // max - min must be cleanly divisible by step size.
            // minMaxStep must be 3 long.
            [['1', '2'], '{ "1", "2" } must have a length between 3 and 3'],
            [['1'], '{ "1" } must have a length between 3 and 3'],
            [[], '{ } must have a length between 3 and 3'],
            // Test for impossible to divide step sizes.
            [['1', '2', '0.3'], 'Step size 0.3 does not divide evenly into 1. Ratio 3.3333333333333 must be an integer.'],
        ];
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::__construct
     *
     * @dataProvider dataMinMaxStepExceptions
     *
     * @param array  $minMaxStep
     *   The minMaxStep array to pass to PriceProposer.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @group stable
     */
    public function testMinMaxStepExceptions($minMaxStep, $message)
    {
        $this->setExpectedException('Exception', $message);

        new PriceProposer($this->orderbook(), $minMaxStep);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\PriceProposer::valid
     *
     * @group stable
     */
    public function testValid()
    {
        $minMaxStep = ['0.0', '0.2', '0.1'];

        list($minPercentile, $maxPercentile, $stepSize) = $minMaxStep;

        $priceProposer = new PriceProposer($this->orderbook(), $minMaxStep);

        // $priceProposer should be valid at the start.
        $this->assertTrue($priceProposer->valid());
        $this->assertLessThanOrEqual($maxPercentile, $priceProposer->key());
        $this->assertSame($minPercentile, $priceProposer->key());

        // $priceProposer should be valid after one step, so key advances by step.
        $priceProposer->next();
        $this->assertLessThanOrEqual($maxPercentile, $priceProposer->key());
        $this->assertSame($minPercentile + $stepSize, $priceProposer->key());
        $this->assertTrue($priceProposer->valid());

        // $priceProposer is still valid after two steps, so key advances by step.
        $priceProposer->next();
        $this->assertLessThanOrEqual($maxPercentile, $priceProposer->key());
        $this->assertSame($minPercentile + $stepSize + $stepSize, $priceProposer->key());
        $this->assertTrue($priceProposer->valid());

        // $priceProposer is not valid after three steps, so key wraps to start.
        $priceProposer->next();
        $this->assertGreaterThan($maxPercentile, $priceProposer->key());
        $this->assertSame($minPercentile + $stepSize + $stepSize + $stepSize, $priceProposer->key());
        $this->assertFalse($priceProposer->valid());

        // $priceProposer is valid once more after a rewind.
        $priceProposer->rewind();
        $this->assertLessThanOrEqual($maxPercentile, $priceProposer->key());
        $this->assertSame($minPercentile, $priceProposer->key());
        $this->assertTrue($priceProposer->valid());
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
        $minMaxStep = ['0.01', '0.1', '0.005'];

        $minPercentile = $minMaxStep[0];
        $stepSize = $minMaxStep[2];

        $priceProposer = new PriceProposer($this->orderbook(), $minMaxStep);

        $currentPercentile = $minPercentile;
        foreach ($priceProposer as $key => $value) {
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
        $priceProposer->rewind();
        $this->assertSame($minPercentile, $priceProposer->key());
        $expectedValue = [
            'bidUSDPrice' => Money::USD((int) ((1 - $priceProposer->key()) * self::PERCENTILE_CAP_MULTIPLIER_BIDS)),
            'askUSDPrice' => Money::USD((int) ($priceProposer->key() * self::PERCENTILE_CAP_MULTIPLIER_ASKS)),
        ];
        $this->assertEquals($expectedValue, $priceProposer->current());
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

        $minMaxStep = ['0.05', '0.1', '0.005'];

        list($minPercentile, $maxPercentile, $stepSize) = $minMaxStep;

        $priceProposer = new PriceProposer($orderbook, $minMaxStep);

        $this->assertSame($minPercentile, $priceProposer->minPercentile());
        $this->assertSame($maxPercentile, $priceProposer->maxPercentile());
        $this->assertSame($stepSize, $priceProposer->stepSize());
        $this->assertSame($minPercentile, $priceProposer->key());
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
            ['0.05', '0.1', '0.05'],
            ['0.01', '0.07', '0.02'],
            ['0.5', '1', '0.5'],
            ['0.9', '1', '0.1'],
            ['0', '1', '1'],
        ];

        array_walk($tests, function($test) {
            $orderbook = $this->orderbook();

            $expected = Money::USD((int) ($test[0] * 12345678));

            $priceProposer = new PriceProposer($orderbook, $test);

            $this->assertEquals($expected, $priceProposer->askUSDPrice(), 'Testing percentile ' . $test[0]);
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
            ['0.05', '0.1', '0.05'],
            ['0.01', '0.07', '0.02'],
            ['0.5', '1', '0.5'],
            ['0.9', '1', '0.1'],
            ['0', '1', '1'],
        ];

        array_walk($tests, function($test) {
            // This mocking gets deep...
            $orderbook = $this->orderbook();

            // bidPrice() passes (1 - $percentile) to percentileCap().
            $expected = Money::USD((int) ((1 - $test[0]) * self::PERCENTILE_CAP_MULTIPLIER_BIDS));

            $priceProposer = new PriceProposer($orderbook, $test);

            $this->assertEquals($expected, $priceProposer->bidUSDPrice(), 'Testing percentile ' . $test[0]);
        });
    }
}
