<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\Fees;
use AppBundle\API\Bitstamp\PrivateAPI\Balance;
use AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs;
use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\Tests\EnvironmentTestTrait;
use AppBundle\API\Bitstamp\Dupes;
use AppBundle\Secrets;
use Money\Money;
use Prophecy\Prophet;
use Prophecy\Argument;

/**
 * Tests for AppBundle\API\Bitstamp\BitstampTradePairs.
 */
class BitstampTradePairsTest extends WebTestCase
{

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

    protected function dupes()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Dupes');
    }

    protected function buysell()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\BuySell');
    }

    protected function proposer()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\PriceProposer');
    }

    protected function tp()
    {
        return new BitstampTradePairs($this->fees(), $this->dupes(), $this->buysell(), $this->proposer());
    }

    protected function statefulProposalMock($isValid = false, $isCompulsory = false, $isFinal = false)
    {
        $proposal = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\TradeProposal');
        $proposal->isValid()->willReturn($isValid);
        $proposal->isCompulsory()->willReturn($isCompulsory);
        $proposal->isFinal()->willReturn($isFinal);

        return $proposal->reveal();
    }

    protected function statefulProposalMockFiller ($isValid = false, $isCompulsory = false, $isFinal = false) {
        return array_fill(0, mt_rand(0, 10), $this->statefulProposalMock($isValid));
    }

    protected function assertActionableReport(array $pre, array $post, array $tests)
    {
        $i = 0;
        do {
            $sequencer = function ($config) {
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

            $pre_sequence = $sequencer($pre);
            $post_sequence = $sequencer($post);

            // Convert the expectations config into mocks.
            $testMocks = array_map(function ($test) {
                return call_user_func_array([$this, 'statefulProposalMock'], $test);
            }, $tests);

            array_walk($testMocks, function ($expected) use ($pre_sequence, $post_sequence) {
                $sequence = array_merge($pre_sequence, [$expected], $post_sequence);

                $actionable = $this->tp()->reduceReportToActionableTradeProposal($sequence);

                $this->assertSame($expected, $actionable);
            });

            $i++;
        } while ($i < 20);
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::reduceReportToActionableTradeProposal
     *
     * @group stable
     */
    public function testReduceReportToActionableTradeProposalFirstValidFinal()
    {
        $pre = [
            // Valid pres have to be tested separately.
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
     */
    public function testReduceReportToActionableTradeProposalNoValid()
    {
        $tests = array_map(function($length) {
            return array_fill(0, $length, $this->statefulProposalMock(false));
        }, range(0, 5));
        // \Psy\Shell::debug(get_defined_vars(), $this);

        array_walk($tests, function($test) {
            $actionable = $this->tp()->reduceReportToActionableTradeProposal($test);
            $this->assertNull($actionable);
        });
    }

    /**
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::validateTradeProposal
     * @covers AppBundle\API\Bitstamp\TradePairs\BitstampTradePairs::__construct
     *
     * @group stable
     */
    public function testValidateTradeProposal()
    {
        // isProfitable, dupeBids, dupeAsks, invalidateReason, panicReason
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

            // We only expect invalidate to be called if the trade is not
            // profitable.
            if (isset($test[3])) {
                $tradeProposalProphet->invalidate($test[3])->shouldBeCalled();
            } else {
                $tradeProposalProphet->invalidate(Argument::any())->shouldNotBeCalled();
            }

            // We expect the bid and ask USD prices to be called in the search
            // for dupes.
            $priceReturn = Money::USD(mt_rand());
            $tradeProposalProphet->bidUSDPrice()->willReturn($priceReturn)->shouldBeCalled();
            $tradeProposalProphet->askUSDPrice()->willReturn($priceReturn)->shouldBeCalled();

            $dupesProphet = $this->prophet->prophesize('\AppBundle\API\Bitstamp\TradePairs\Dupes');
            $dupesProphet->bids($priceReturn)->willReturn($test[1])->shouldBeCalled();
            $dupesProphet->asks($priceReturn)->willReturn($test[2])->shouldBeCalled();

            // We only expect panic to be called if there is a dupe.
            if (isset($test[4])) {
                $tradeProposalProphet->panic($test[4])->shouldBeCalled();
            } else {
                $tradeProposalProphet->panic(Argument::any())->shouldNotBeCalled();
            }

            // We expect validate() to be called unconditionally as it is always
            // overridden by a higher state anyway.
            $tradeProposalProphet->validate()->shouldBeCalled();

            // Attempt validation.
            $tp = new BitstampTradePairs($this->fees(), $dupesProphet->reveal(), $this->buysell(), $this->proposer());
            $tp->validateTradeProposal($tradeProposalProphet->reveal());
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
        array_walk($tests, function($test) {
            $this->setIsTrading($test[0]);
            $this->assertEquals($test[1], $this->tp()->isTrading());
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
        $this->tp()->execute();
    }
}
