<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use AppBundle\Secrets;
use AppBundle\Tests\EnvironmentTestTrait;
use Money\Money;
use Respect\Validation\Validator as v;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function Functional\map;
use function Functional\each;

/**
 * Tests AppBundle\API\Bitstamp\TradePairs\TradeProposal.
 */
class TradeProposalTest extends WebTestCase
{
    use EnvironmentTestTrait;

    /**
     * @param string $class
     */
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

    /**
     * @return Secrets
     */
    protected function secrets()
    {
        return $this->mock('AppBundle\Secrets');
    }

    protected function tradeProposal()
    {
        return new TradeProposal($this->randomBidAskPrices(), $this->fees(), $this->secrets());
    }

    protected function tradeProposalLiveSecrets()
    {
        return new TradeProposal($this->randomBidAskPrices(), $this->fees(), new Secrets());
    }

    protected function randomBidAskPrices()
    {
        return ['bidUSDPrice' => Money::USD(mt_rand()), 'askUSDPrice' => Money::USD(mt_rand())];
    }

    protected function faker()
    {
        return \Faker\Factory::create();
    }

    /**
     * @param string $checkMethod
     * @param boolean $expected
     */
    protected function assertBooleanAfterMethods(array $methods, $checkMethod, $expected)
    {
        $proposal = $this->tradeProposal();

        // Generate a random reason.
        $reason = $this->faker()->sentence;

        if (!empty($methods)) {
            $setReturn = null;
            foreach ($methods as $method) {
                $setReturn = $proposal->{$method}($reason);
            }

            // Check the value of the last setter return. It should be the same as
            // the getter.
            $this->assertSame((bool) $expected, $setReturn);
        }

        // Double check the return.
        $this->assertSame((bool) $expected, $proposal->{$checkMethod}());
        $this->assertSame((bool) $expected, $proposal->{$checkMethod}());
    }

    /**
     * @param string $method
     */
    protected function methodRangeArray($method, $start = 0, $end = 5)
    {
        v::string()->check($method);
        v::int()->check($start);
        v::int()->check($end);

        return map(range($start, $end), function($times) use ($method) {
            return array_fill(0, $times, $method);
        });
    }

    /**
     * @param string $method
     * @param string $checkMethod
     * @param boolean $expected
     */
    protected function assertBooleanAfterMethodRange($method, $checkMethod, $expected, $start = 0, $end = 5)
    {
        $range = $this->methodRangeArray($method, $start, $end);
        array_walk($range, function($methods) use ($checkMethod, $expected) {
            $this->assertBooleanAfterMethods($methods, $checkMethod, $expected);
        });
    }

    /**
     * Data provider for exceptions in __construct.
     *
     * @return array
     */
    public function dataConstructExceptions()
    {
        return [
            [['bidUSDPrice' => new \StdClass(), 'askUSDPrice' => Money::USD(1)], '`[object] (stdClass: { })` must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => Money::BTC(1), 'askUSDPrice' => new \StdClass()], '`[object] (stdClass: { })` must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => new \StdClass(), 'askUSDPrice' => new \StdClass()], '`[object] (stdClass: { })` must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => 1, 'askUSDPrice' => Money::USD(1)], '1 must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => Money::USD(1), 'askUSDPrice' => 1], '1 must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => Money::USD(0), 'askUSDPrice' => 0], '0 must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => 0, 'askUSDPrice' => Money::BTC(0)], '0 must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => Money::USD(0), 'askUSDPrice' => Money::USD(0), 'foo' => Money::USD(0)], '{ "bidUSDPrice": `[object] (Money\Money: { })`, "askUSDPrice": `[object] (Money\Money: { })`, "foo": `[object] (Money\Money: { })` } must have a length between 2 and 2'],
            [['bidUSDPrice' => Money::USD(0)], '{ "bidUSDPrice": `[object] (Money\Money: { })` } must have a length between 2 and 2'],
            [[], '{ } must have a length between 2 and 2'],
            // Check for null values.
            [['bidUSDPrice' => null, 'askUSDPrice' => null], 'null must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => Money::USD(0), 'askUSDPrice' => null], 'null must be an instance of "Money\\\Money"'],
            [['bidUSDPrice' => null, 'askUSDPrice' => Money::USD(0)], 'null must be an instance of "Money\\\Money"'],
        ];
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::__construct
     *
     * @param array  $notPrices
     *   An array of things that are not prices.
     *
     * @param string $message
     *   An exception string.
     *
     * @dataProvider dataConstructExceptions
     *
     * @group stable
     */
    public function testConstructExceptions(array $notPrices, $message)
    {
        $this->setExpectedException('Exception', $message);

        new TradeProposal($notPrices, $this->fees(), $this->secrets());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::isFinal
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::ensureFinal
     *
     * @group stable
     */
    public function testFinal()
    {
        // No matter how many times we call isFinal, it should be false.
        $this->assertBooleanAfterMethodRange('isFinal', 'isFinal', false);
        // After calling ensureFinal, isFinal must be true.
        $this->assertBooleanAfterMethodRange('ensureFinal', 'isFinal', true, 1, 5);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::isCompulsory
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::ensureCompulsory
     *
     * @group stable
     */
    public function testCompulsory()
    {
        // No matter how many times we call isCompulsory, it should be false.
        $this->assertBooleanAfterMethodRange('isCompulsory', 'isCompulsory', false);
        // After calling ensureCompulsory, isCompulsory must be true.
        $this->assertBooleanAfterMethodRange('ensureCompulsory', 'isCompulsory', true, 1, 5);
        // After calling ensureCompulsory, isFinal must be true.
        $this->assertBooleanAfterMethodRange('ensureCompulsory', 'isFinal', true, 1, 5);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::reasons
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::addReason
     *
     * @group stable
     *
     * @return void
     */
    public function testReasons()
    {
        // Get a bunch of random sentences.
        $reasons = map(range(0, 50), function() {
            return $this->faker()->sentence;
        });

        // Add a range of numeric strings that runs through 0.
        $reasons = array_merge($reasons, map(range(-10, 10), function($item) {
            return (string) $item;
        }));

        // Generate a random method to test.
        $nextMethod = function() {
            $methods = [
                'invalidate',
                'ensureCompulsory',
                'ensureFinal',
            ];
            shuffle($methods);

            return reset($methods);
        };

        $proposal = $this->tradeProposal();

        array_walk($reasons, function($reason) use (&$proposal, $nextMethod) {
            $proposal->{$nextMethod()}($reason);
        });

        $this->assertSame($reasons, $proposal->reasons());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::isValid
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::validate
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::invalidate
     *
     * @group stable
     *
     * @return void
     */
    public function testIsValid()
    {
        $range = range(1, 10);

        // Test calling validate a bunch of times and seeing true.
        $validateXTimes = map($range, function($times) {
            return array_fill(0, $times, 'validate');
        });
        shuffle($validateXTimes);

        array_walk($validateXTimes, function($validations) {
            $this->assertBooleanAfterMethods($validations, 'isValid', true);
        });

        // Test calling invalidate a bunch of times and seeing false.
        $invalidateXTimes = map($range, function($times) {
            return array_fill(0, $times, 'invalidate');
        });
        shuffle($invalidateXTimes);

        array_walk($invalidateXTimes, function($invalidations) {
            $this->assertBooleanAfterMethods($invalidations, 'isValid', false);
        });

        // Test that a random mix of validate and invalidate is false.
        $randomXTimes = array_map(function($valid, $invalid) {
            $merged = array_merge($valid, $invalid);
            shuffle($merged);

            return $merged;
        }, $validateXTimes, $invalidateXTimes);

        array_walk($randomXTimes, function($invalidated) {
            $this->assertBooleanAfterMethods($invalidated, 'isValid', false);
        });

        $singleValidateInvalidate = ['validate', 'invalidate'];
        $this->assertBooleanAfterMethods($singleValidateInvalidate, 'isValid', false);

        $singleInvalidateValidate = ['invalidate', 'validate'];
        $this->assertBooleanAfterMethods($singleInvalidateValidate, 'isValid', false);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::isValid
     *
     * @group stable
     */
    public function testIsValidNullException()
    {
        $this->setExpectedException('Exception');
        $this->tradeProposal()->isValid();
    }

    /**
     * Data provider for invalid reasons.
     *
     * @return void
     */
    public function dataInvalidReason()
    {
        // Generate some data that is not a string.
        $data = map(range(0, 10), function($index) {
            $invalidReasonTypes = ['randomDigit', 'randomFloat', 'words', 'dateTime'];

            return $this->faker()->{$invalidReasonTypes[$index % count($index)]};
        });

        // Test the empty string.
        $data[] = '';

        // Test a blank data point.
        $data[] = [];

        // Shuffle the data.
        shuffle($data);

        // Wrap each data point in an array.
        $data = map($data, function($item) {
            return (array) $item;
        });

        return $data;
    }

    /**
     * @dataProvider dataInvalidReason
     *
     * @param mixed $invalidReason
     *   Anything that is not a valid reason string.
     *
     * @group stable
     */
    public function testInvalidateInvalidReasonException($invalidReason = null)
    {
        $this->setExpectedException('Exception');
        $this->tradeProposal()->invalidate($invalidReason);
    }

    /**
     * @dataProvider dataInvalidReason
     *
     * @param mixed $invalidReason
     *   Anything that is not a valid reason string.
     *
     * @group stable
     */
    public function testCompulsoryInvalidReasonException($invalidReason = null)
    {
        $this->setExpectedException('Exception');
        $this->tradeProposal()->ensureCompulsory($invalidReason);
    }

    /**
     * @dataProvider dataInvalidReason
     *
     * @param mixed $invalidReason
     *   Anything that is not a valid reason string.
     *
     * @group stable
     */
    public function testFinalInvalidReasonException($invalidReason = null)
    {
        $this->setExpectedException('Exception');
        $this->tradeProposal()->ensureFinal($invalidReason);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDPrice
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::__construct
     *
     * @group stable
     */
    public function testBidUSDPrice()
    {
        $prices = $this->randomBidAskPrices();
        $tradeProposal = new TradeProposal($prices, $this->fees(), $this->secrets());
        $this->assertEquals($prices['bidUSDPrice'], $tradeProposal->bidUSDPrice());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolumeBase
     *
     * @group stable
     */
    public function testBidUSDVolumeBase()
    {
        // Check basic functionality.
        $minVolume = mt_rand();
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $minVolume);
        $this->assertEquals(Money::USD($minVolume), $this->tradeProposalLiveSecrets()->bidUSDVolumeBase());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolume
     *
     * @group stable
     *
     * @return void
     */
    public function testBidUSDVolume()
    {
        // Check that the isofee is returned.
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
        $fees = $this->fees();
        $isoFeeAmount = mt_rand();
        $fees->method('isofeeMaxUSD')->willReturn(Money::USD($isoFeeAmount));
        $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees, new Secrets());
        $this->assertEquals(Money::USD($isoFeeAmount), $tradeProposal->bidUSDVolume());

        // Check that the volume for a known isofee is returned. We mock
        // isofee to just return 2x the base value.
        $tests = [
            ['5', 10],
            ['10', 20],
        ];
        array_walk($tests, function($test) {
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $test[0]);

            $fees = $this->fees();
            $fees->method('isofeeMaxUSD')->will($this->returnCallback(function(Money $usd) {
                return $usd->multiply(2);
            }));

            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees, new Secrets());
            $this->assertEquals(Money::USD($test[1]), $tradeProposal->bidUSDVolume());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidUSDVolumePlusFees
     *
     * @group stable
     */
    public function testBidUSDVolumePlusFees()
    {
        // The USD bid volume post fees is equal to the max isofee USD volume
        // plus the absolute value of USD fees.
        // absoluteFeeUSD, isofeeMaxUSD, expected.
        $tests = [
        [Money::USD(2340), Money::USD(3450), Money::USD(5790)],
        [Money::USD(0), Money::USD(0), Money::USD(0)],
        [Money::USD(1), Money::USD(0), Money::USD(1)],
        [Money::USD(0), Money::USD(1), Money::USD(1)],
        [Money::USD(-1), Money::USD(0), Money::USD(-1)],
        [Money::USD(0), Money::USD(-1), Money::USD(-1)],
        ];

        array_walk($tests, function($test) {
            // This can be anything.
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());

            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);

            // USD bid volume has nothing to do with prices.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees, new Secrets());

            $this->assertEquals($test[2], $tradeProposal->bidUSDVolumePlusFees());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::bidBTCVolume
     *
     * @group stable
     */
    public function testBidBTCVolume()
    {
        // bidUSDVolume amount, bidUSDPrice amount, expected satoshis amount.
        // The amounts are converted to Money in the test for convenience.
        $tests = [
            // Basic tests.
            [100, 50, 2 * 10 ** 8],
            [2, 1, 2 * 10 ** 8],
            [1, 2, 5 * 10 ** 7],
            // 999 / 100000000 * 10 ** 8 = 999.0 => will floor to 998
            [999, 100000000, 998],
            // 99 / 100000000 * 10 ** 8 = 99.0 => will floor to 99
            [999, 1000000000, 99],
            [111, 100000000, 111],
            [111, 1000000000, 11],
        ];
        array_walk($tests, function($test) {
            // This can be set to anything here.
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
            $fees = $this->fees();
            $fees->method('isofeeMaxUSD')->willReturn(Money::USD($test[0]));
            $prices = ['bidUSDPrice' => Money::USD($test[1]), 'askUSDPrice' => Money::USD(mt_rand())];

            $tradeProposal = new TradeProposal($prices, $fees, new Secrets());
            $this->assertEquals(Money::BTC($test[2]), $tradeProposal->bidBTCVolume());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::askUSDPrice
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::__construct
     *
     * @group stable
     */
    public function testAskUSDPrice()
    {
        $askUSDPrice = Money::USD(mt_rand());
        $prices = [
            'bidUSDPrice' => Money::USD(mt_rand()),
            'askUSDPrice' => $askUSDPrice,
        ];
        $tp = new TradeProposal($prices, $this->fees(), $this->secrets());
        $this->assertEquals($askUSDPrice, $tp->askUSDPrice());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::askUSDVolumeCoverFees
     *
     * @group stable
     */
    public function testAskUSDVolumeCoverFees()
    {
        // The USD ask volume required to cover the bid volume, desired USD
        // profit and all fees is:
        // (bidUSDPostFees + minUSDProfit) / feeAskMultiplier
        // => (absoluteFeeUSD + isofeeMaxUSD + minProfitUSD) / feeAskMultiplier
        // We get the ceiling of this to be absolutely sure we've covered
        // everything.
        // @see volumeUSDAsk() documentation.
        // absoluteFeeUSD, isofeeMaxUSD, minProfitUSD, feeAskMultiplier,
        // expected.
        $tests = [
        // Simple cases.
        [Money::USD(0), Money::USD(0), '0', 1, Money::USD(0)],
        [Money::USD(1), Money::USD(1), '1', 3, Money::USD(1)],
        [Money::USD(2), Money::USD(2), '2', 3, Money::USD(2)],
        // Flush out failures to handle min USD profit setting.
        [Money::USD(100), Money::USD(200), '300', 0.5, Money::USD(1200)],
        // Test for something where ceiling will matter.
        [Money::USD(123), Money::USD(234), '345', 0.456, Money::USD(1540)],
        ];

        array_walk($tests, function($test) {
            $this->setEnv('BITSTAMP_MIN_USD_VOLUME', mt_rand());
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $test[2]);

            $fees = $this->fees();
            $fees->method('absoluteFeeUSD')->willReturn($test[0]);
            $fees->method('isofeeMaxUSD')->willReturn($test[1]);
            $fees->method('asksMultiplier')->willReturn($test[3]);

            // The USD volume has nothing to do with the price.
            $tradeProposal = new TradeProposal($this->randomBidAskPrices(), $fees, new Secrets());

            $this->assertEquals($test[4], $tradeProposal->askUSDVolumeCoverFees());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::minProfitBTC
     *
     * @group stable
     */
    public function testMinProfitBTC()
    {
        // sets, expects.
        $scenarios = [
            ['0', Money::BTC(0)],
            ['1', Money::BTC(1)],
            ['100', Money::BTC(100)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_BTC_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposalLiveSecrets()->minProfitBTC());
        };
        array_walk($scenarios, $test);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\TradeProposal::minProfitUSD
     *
     * @group stable
     */
    public function testMinProfitUSD()
    {
        // sets, expects.
        $scenarios = [
            ['0', Money::USD(0)],
            ['1', Money::USD(1)],
            ['10', Money::USD(10)],
            ['100', Money::USD(100)],
        ];
        $test = function($scenario) {
            $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $scenario[0]);
            $this->assertEquals($scenario[1], $this->tradeProposalLiveSecrets()->minProfitUSD());
        };
        array_walk($scenarios, $test);
    }
}
