<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\GuzzleTestTrait;
use AppBundle\API\Bitstamp\PrivateAPI\OpenOrders;
use AppBundle\API\Bitstamp\Dupes;
use AppBundle\Secrets;
use Money\Money;

/**
 * Tests AppBundle\API\Bitstamp\Dupes
 */
class DupesTest extends WebTestCase
{
    use GuzzleTestTrait;

    protected $sample = '[{"price":"237.50","amount":"0.03397937","type":1,"id":67290521,"datetime":"2015-05-16 21:30:19"},{"price":"232.95","amount":"0.03434213","type":0,"id":67290522,"datetime":"2015-05-16 21:30:19"},{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]';
    protected $sample2 = '[{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]';

    protected function openOrders()
    {
        return new OpenOrders($this->client(), $this->mockLogger(), $this->mockAuthenticator());
    }

    protected function dupes()
    {
        return new Dupes($this->openOrders(), new Secrets());
    }

    protected function setRangeMultiplier($multiplier)
    {
        putenv('DUPES_RANGE_MULTIPLIER=' . $multiplier);
    }

    /**
     * Tests that we can get the range multiplier environment variable.
     *
     * @group stable
     */
    public function testRangeMultiplier()
    {
        $rms = [0.01, 0.005];
        foreach ($rms as $rm) {
            $this->setRangeMultiplier($rm);
            $this->assertSame($rm, $this->dupes()->rangeMultiplier());
        }
    }

    /**
     * Tests calculation of range, upper and lower bounds from the multiplier.
     *
     * @group stable
     */
    public function testBounds()
    {
        // Range multiplier, price, range, upper, lower.
        $tests = [
            ['0.01', Money::USD(100), Money::USD(1), Money::USD(101), Money::USD(99)],
            ['0.004', Money::USD(100), Money::USD(0), Money::USD(100), Money::USD(100)],
            ['0.01', Money::USD(10), Money::USD(0), Money::USD(10), Money::USD(10)],
            ['0.01', Money::USD(23750), Money::USD(238), Money::USD(23988), Money::USD(23512)],
        ];
        foreach ($tests as $test) {
            $this->setRangeMultiplier($test[0]);
            $this->assertEquals(['range' => $test[2], 'upper' => $test[3], 'lower' => $test[4]], $this->dupes()->bounds($test[1]));
        }
    }

    /**
     * Tests prices that are known dupes and not dupes against OO test data.
     *
     * Given:
     *   - OO price = X
     *   - Test price = Y
     *   - Range multiplier = R
     *   - Range above = (1 + R) = Ra
     *   - Range below = (1 - R) = Rb
     *
     * Dupe if:
     *   - Y * (1 + R) > X || Y * (1 - R) < X
     *   - Y > X / (1 + R) || Y < X / (1 - R)
     *   - Y > X / Ra || Y < X / Rb
     *
     * @group stable
     *
     * @see http://www.mathsisfun.com/algebra/inequality-solving.html
     *
     * @return [type] [description]
     */
    public function testDupes()
    {
        // Range multiplier, method, price to check, expected dupes.
        $askTests = [
            // Exact match.
            ['0.01', 'asks', Money::USD(23750), [Money::USD(23750)]],
            // Very close.
            ['0.01', 'asks', Money::USD(23751), [Money::USD(23750)]],
            ['0.01', 'asks', Money::USD(23749), [Money::USD(23750)]],
            // Maximum below = 23,514.851485 as X / Ra
            // - rounded = 23515
            // - must be gt not gte = 23516
            ['0.01', 'asks', Money::USD(23516), [Money::USD(23750)]],
            ['0.01', 'asks', Money::USD(23515), []],
            // Maximum above = 23,989.89899 as X / Rb
            // - Rounded = 23990
            // - must be lt not lte = 23989
            // - We also come within range of OO at 241.45, with upper bound 24229
            ['0.01', 'asks', Money::USD(23989), [Money::USD(23750), Money::USD(24145)]],
            ['0.01', 'asks', Money::USD(23990), [Money::USD(24145)]],
            // Exact match.
            ['0.005', 'asks', Money::USD(23750), [Money::USD(23750)]],
            // Very close.
            ['0.005', 'asks', Money::USD(23751), [Money::USD(23750)]],
            ['0.005', 'asks', Money::USD(23749), [Money::USD(23750)]],
            // Maximum below = 23,631.840796 as X / Ra
            // - rounded = 23632
            // - must be gt not gte = 23633
            ['0.005', 'asks', Money::USD(23633), [Money::USD(23750)]],
            ['0.005', 'asks', Money::USD(23632), []],
            // Maximum above = 23,869.346734 as X / Rb
            // - Rounded = 23869
            // - must be lt not lte = 23868
            // - We do not come within range of OO at 241.45, with upper bound 23987
            ['0.005', 'asks', Money::USD(23868), [Money::USD(23750)]],
            ['0.005', 'asks', Money::USD(23869), []],
            // Exact match.
            ['0.01', 'bids', Money::USD(23295), [Money::USD(23295)]],
            // Very close.
            ['0.01', 'bids', Money::USD(23296), [Money::USD(23295)]],
            ['0.01', 'bids', Money::USD(23294), [Money::USD(23295)]],
            // Maximum below = 23,064.356436 as X / Ra
            // - rounded = 23064
            // - must be gt not gte = 23065
            ['0.01', 'bids', Money::USD(23065), [Money::USD(23295)]],
            ['0.01', 'bids', Money::USD(23064), []],
            // Maximum above = 23,530.30303 as X / Rb
            // - Rounded = 23530
            // - must be lt not lte = 23529
            ['0.01', 'bids', Money::USD(23529), [Money::USD(23295)]],
            ['0.01', 'bids', Money::USD(23530), []],
            // Exact match.
            ['0.005', 'bids', Money::USD(23295), [Money::USD(23295)]],
            // Very close.
            ['0.005', 'bids', Money::USD(23296), [Money::USD(23295)]],
            ['0.005', 'bids', Money::USD(23294), [Money::USD(23295)]],
            // Maximum below = 23,179.104478 as X / Ra
            // - rounded = 23179
            // - must be gt not gte = 23180
            ['0.005', 'bids', Money::USD(23180), [Money::USD(23295)]],
            ['0.005', 'bids', Money::USD(23179), []],
            // Maximum above = 23,412.060302 as X / Rb
            // - Rounded = 23412
            // - must be lt not lte = 23411
            ['0.005', 'bids', Money::USD(23411), [Money::USD(23295)]],
            ['0.005', 'bids', Money::USD(23412), []],
        ];

        foreach ($askTests as $test) {
            putenv('DUPES_RANGE_MULTIPLIER=' . $test[0]);
            $this->assertEquals($test[3], $this->dupes()->$test[1]($test[2]));
        }
    }
}
