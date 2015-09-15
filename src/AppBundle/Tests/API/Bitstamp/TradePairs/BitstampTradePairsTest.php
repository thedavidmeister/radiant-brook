<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use AppBundle\API\Bitstamp\Dupes;
use AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs;
use AppBundle\API\Bitstamp\TradePairs\Fees;
use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use AppBundle\Secrets;
use AppBundle\Tests\EnvironmentTestTrait;
use Money\Money;
use Prophecy\Argument;
use Prophecy\Prophet;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{
    protected $prophet;

    use EnvironmentTestTrait;

    protected function setup()
    {
        $this->prophet = new Prophet();
    }

    protected function tearDown()
    {
        $this->prophet->checkPredictions();
    }

    protected function setIsTrading($isTrading)
    {
        $this->setEnv('BITSTAMP_IS_TRADING', $isTrading);
    }

    protected function setMinUSDVolume($volume)
    {
        $this->setEnv('BITSTAMP_MIN_USD_VOLUME', $volume);
    }

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

    /**
     * @return Fees
     */
    protected function fees()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Fees');
    }

    /**
     * @return \AppBundle\API\Bitstamp\TradePairs\Dupes
     */
    protected function dupes()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Dupes');
    }

    /**
     * @return \AppBundle\API\Bitstamp\TradePairs\BuySell
     */
    protected function buysell()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\BuySell');
    }

    /**
     * @return \AppBundle\API\Bitstamp\TradePairs\PriceProposer
     */
    protected function proposer()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\PriceProposer');
    }

    /**
     * @return Secrets
     */
    protected function secrets()
    {
        return $this->mock('\AppBundle\Secrets');
    }

    protected function tradePairs()
    {
        return new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->proposer(), $this->secrets());
    }

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy
     */
    protected function statefulProposalMockRaw($isValid = false, $isCompulsory = false, $isFinal = false)
    {
        $proposal = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');
        $proposal->isValid()->willReturn($isValid);
        $proposal->isCompulsory()->willReturn($isCompulsory);
        $proposal->isFinal()->willReturn($isFinal);

        return $proposal;
    }

    protected function statefulProposalMock($isValid = false, $isCompulsory = false, $isFinal = false)
    {
        $proposal = $this->statefulProposalMockRaw($isValid, $isCompulsory, $isFinal);

        return $proposal->reveal();
    }

    protected function statefulProposalMockFiller($isValid = false, $isCompulsory = false, $isFinal = false)
    {
        return array_fill(0, mt_rand(0, 10), $this->statefulProposalMock($isValid, $isCompulsory, $isFinal));
    }

    protected function assertActionableReport(array $pre, array $post, array $tests)
    {
        $i = 0;
        do {
            $sequencer = function($config) {
                // Fill an array with statful mocks based on the config options.
                $sequence = array_reduce($config, function(array $carry, array $args) {
                    // Merge arrays from the filler together to build the
                    // sequence.
                    return array_merge($carry, call_user_func_array([$this, 'statefulProposalMockFiller'], $args));
                }, []);

                // Randomise the sequence for fun.
                shuffle($sequence);

                return $sequence;
            };

            $preSequence = $sequencer($pre);
            $postSequence = $sequencer($post);

            // Convert the expectations config into mocks.
            $testMocks = array_map(function($test) {
                return call_user_func_array([$this, 'statefulProposalMock'], $test);
            }, $tests);

            array_walk($testMocks, function($expected) use ($preSequence, $postSequence) {
                $sequence = array_merge($preSequence, [$expected], $postSequence);

                $actionable = $this->tradePairs()->reduceReportToActionableTradeProposal($sequence);

                $this->assertSame($expected, $actionable);
            });

            $i++;
        } while ($i < 5);
    }

    protected function isRandomBool()
    {
        return (bool) mt_rand(0, 1);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::fees
     *
     * @group stable
     */
    public function testFees()
    {
        $fees = $this->fees();
        $tradePairs = new BitstampTradePairs($fees, $this->dupes(), $this->buysell(), $this->proposer(), $this->secrets());

        $this->assertSame($fees, $tradePairs->fees());
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::validateTradeProposal
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::__construct
     *
     * @group stable
     */
    public function testValidateTradeProposal()
    {
        // isProfitable, dupeBids, dupeAsks, invalidateReason, finalReason
        $tests = [
            [true, [], [], null, null],
            [false, [], [], 'Not a profitable trade proposition.', null],
            [true, [uniqid()], [], null, 'Duplicate trade pairs found.'],
            [true, [], [uniqid()], null, 'Duplicate trade pairs found.'],
            [true, [uniqid()], [uniqid()], null, 'Duplicate trade pairs found.'],
            [false, [uniqid()], [], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
            [false, [], [uniqid()], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
            [false, [uniqid()], [uniqid()], 'Not a profitable trade proposition.', 'Duplicate trade pairs found.'],
        ];

        array_walk($tests, function($test) {
            $tradeProposalProphet = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');

            // We expect isProfitable() to be called, to check for profit
            // invalidation.
            $tradeProposalProphet->isProfitable()->willReturn($test[0])->shouldBeCalled();

            // We expect shouldNotBeValid to be called if the trade is not
            // profitable or has dupes.
            $invalid = false;
            if (!$test[0]) {
                $invalid = true;
                $tradeProposalProphet->shouldNotBeValid($test[3])->shouldBeCalled();
            }
            if (!empty($test[1]) || !empty($test[2])) {
                $invalid = true;
                $tradeProposalProphet->shouldNotBeValid($test[4])->shouldBeCalled();
            }
            if (!$invalid) {
                $tradeProposalProphet->shouldNotBeValid(Argument::any())->shouldNotBeCalled();
            }

            // We expect the bid and ask USD prices to be called in the search
            // for dupes.
            $priceReturn = Money::USD(mt_rand());
            $tradeProposalProphet->bidUSDPrice()->willReturn($priceReturn)->shouldBeCalled();
            $tradeProposalProphet->askUSDPrice()->willReturn($priceReturn)->shouldBeCalled();

            $dupesProphet = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\Dupes');
            $dupesProphet->bids($priceReturn)->willReturn($test[1])->shouldBeCalled();
            $dupesProphet->asks($priceReturn)->willReturn($test[2])->shouldBeCalled();

            // We only expect shouldBeFinal to be called if there is a dupe.
            if (!empty($test[1]) || !empty($test[2])) {
                $tradeProposalProphet->shouldNotBeValid($test[4])->shouldBeCalled();
                $tradeProposalProphet->shouldBeFinal($test[4])->shouldBeCalled();
            } else {
                $tradeProposalProphet->shouldBeFinal(Argument::any())->shouldNotBeCalled();
            }

            // We expect shouldBeValid() to be called unconditionally as it is
            // always overridden if appropriate anyway.
            $tradeProposalProphet->shouldBeValid()->shouldBeCalled();

            // Attempt validation.
            $tradePairs = new BitstampTradePairs($this->fees(), $dupesProphet->reveal(), $this->buysell(), $this->proposer(), $this->secrets());
            $tradePairs->validateTradeProposal($tradeProposalProphet->reveal());
        });
    }

    /**
     * Data provider for invalid trade reports.
     *
     * @return array[]
     */
    public function dataReduceReportToActionableTradeProposalExceptions()
    {
        $tests = [
            [['foo'], '"foo" must be an instance of "\\\AppBundle\\\API\\\Bitstamp\\\TradePairs\\\TradeProposalInterface"'],
            [[[]], '{ } must be an instance of "\\\AppBundle\\\API\\\Bitstamp\\\TradePairs\\\TradeProposalInterface"'],
            [[new \StdClass()], '`[object] (stdClass: { })` must be an instance of "\\\AppBundle\\\API\\\Bitstamp\\\TradePairs\\\TradeProposalInterface"'],
        ];

        // Only one of the report elements is invalid.
        $prophet = new Prophet();
        $mockProposal = $prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');
        $tests[] = [[$mockProposal->reveal(), 'foo'], '"foo" must be an instance of "\\\AppBundle\\\API\\\Bitstamp\\\TradePairs\\\TradeProposalInterface"'];

        return $tests;
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @dataProvider dataReduceReportToActionableTradeProposalExceptions
     *
     * @param array  $invalidReport
     *   A report array that does not contain a valid report.
     *
     * @param string $message
     *   An exception string.
     *
     * @group stable
     */
    public function testReduceReportToActionableTradeProposalExceptions(array $invalidReport, $message)
    {
        $this->setExpectedException('Exception', $message);

        $this->tradePairs()->reduceReportToActionableTradeProposal($invalidReport);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     *
     * @return void
     */
    public function testReduceReportToActionableTradeProposalInvalidFinal()
    {
        // Start with an invalid mock.
        $first = $this->statefulProposalMockRaw(false);
        // Should be checked.
        $first->isValid()->shouldBeCalled();
        $first->isCompulsory()->shouldBeCalled();
        $first->isFinal()->shouldBeCalled();

        // Then an invalid final.
        $second = $this->statefulProposalMockRaw(false, false, true);
        // Should be checked.
        $second->isValid()->shouldBeCalled();
        $second->isCompulsory()->shouldBeCalled();
        $second->isFinal()->shouldBeCalled();

        // Then whatever.
        $third = $this->statefulProposalMockRaw($this->isRandomBool(), $this->isRandomBool(), $this->isRandomBool());
        $third->isValid()->shouldNotBeCalled();
        $third->isCompulsory()->shouldNotBeCalled();
        $third->isFinal()->shouldNotBeCalled();

        // Build the sequence.
        $sequence = [$first, $second, $third];
        // Reveal the sequence.
        $sequence = array_map(function($item) {
            return $item->reveal();
        }, $sequence);

        $actionable = $this->tradePairs()->reduceReportToActionableTradeProposal($sequence);

        $this->assertNull($actionable);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     *
     * @return void
     */
    public function testReduceReportToActionableTradeProposalPreValidFinal()
    {
        // Start with a valid mock.
        $first = $this->statefulProposalMockRaw(true);
        // Should be checked.
        $first->isValid()->shouldBeCalled();
        $first->isCompulsory()->shouldBeCalled();
        $first->isFinal()->shouldBeCalled();

        // Then a final mock.
        $second = $this->statefulProposalMockRaw(true, false, true);
        // Should be checked.
        $second->isValid()->shouldBeCalled();
        $second->isCompulsory()->shouldBeCalled();
        $second->isFinal()->shouldBeCalled();

        // Then something else.
        $third = $this->statefulProposalMockRaw($this->isRandomBool(), $this->isRandomBool(), $this->isRandomBool());
        // This should not be checked.
        $third->isValid()->shouldNotBeCalled();
        $third->isCompulsory()->shouldNotBeCalled();
        $third->isFinal()->shouldNotBeCalled();

        // Build the sequence.
        $sequence = [$first, $second, $third];
        // Reveal the sequence.
        $sequence = array_map(function($item) {
            return $item->reveal();
        }, $sequence);

        $actionable = $this->tradePairs()->reduceReportToActionableTradeProposal($sequence);

        $this->assertSame($sequence[0], $actionable);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     */
    public function testReduceReportToActionableTradeProposalFirstValidFinal()
    {
        $pre = [
            // Valid pre-mocks have to be tested separately.
            // @see testReduceReportToActionableTradeProposalPreValidFinal()
            [false],
        ];
        $post = [
            [true],
            [false],
            [true, true],
            [false, true],
            [true, true, true],
            [true, false, true],
            [false, true, true],
            [false, false, true],
        ];
        // Invalid finals have to be tested separately.
        // @see testReduceReportToActionableTradeProposalInvalidFinal()
        $tests = [
            [true, true, true],
            [true, false, true],
        ];
        $this->assertActionableReport($pre, $post, $tests);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     */
    public function testReduceReportToActionableTradeProposalFirstCompulsory()
    {
        $pre = [
            [false],
            [true],
        ];
        $post = [
            [false],
            [true],
            [false, true],
            [false, false],
        ];
        $tests = [
            [false, true],
            [true, true],
        ];
        $this->assertActionableReport($pre, $post, $tests);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     */
    public function testReduceReportToActionableTradeProposalFirstValid()
    {
        $pre = [
            [false],
        ];
        $post = [
            [false],
            [true],
        ];
        $tests = [
            [true],
        ];
        $this->assertActionableReport($pre, $post, $tests);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     *
     * @return void
     */
    public function testReduceReportToActionableTradeProposalNoValid()
    {
        $tests = array_map(function($length) {
            return array_fill(0, $length, $this->statefulProposalMock(false));
        }, range(0, 5));

        array_walk($tests, function($test) {
            $actionable = $this->tradePairs()->reduceReportToActionableTradeProposal($test);
            $this->assertNull($actionable);
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::isTrading
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::__construct
     *
     * @group stable
     */
    public function testIsTrading()
    {
        // env value, expected.
        $tests = [
            // Truthy strings.
            ['true', true],
            ['TRUE', true],
            ['1', true],
            ['yes', true],
            // Other strings.
            ['', false],
            ['no', false],
            ['one', false],
            ['foo', false],
            ['false', false],
            ['FALSE', false],
            ['0', false],
            // filter_var() doesn't recognise y/n.
            ['y', false],
            ['n', false],
        ];
        $tpWithLiveSecrets = new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->proposer(), new Secrets());
        array_walk($tests, function($test) use ($tpWithLiveSecrets) {
            $this->setIsTrading($test[0]);
            $this->assertEquals($test[1], $tpWithLiveSecrets->isTrading());
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::execute
     *
     * @group stable
     */
    public function testExecuteExceptionNotTrading()
    {
        $this->setIsTrading('0');
        $this->setExpectedException('Exception', 'Bitstamp trading is disabled at this time.');
        $this->tradePairs()->execute();
    }
}
