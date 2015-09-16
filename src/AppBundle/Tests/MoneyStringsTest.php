<?php

namespace AppBundle\Tests;

use AppBundle\MoneyStringsUtil;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Respect\Validation\Validator as v;

/**
 * Tests for AppBundle\MoneyStringsUtil
 */
class MoneyStringsTest extends WebTestCase
{
    /**
     * Data provider for testStringToUSDTypeExceptions().
     *
     * @return array[]
     */
    public function dataStringToXTypeExceptions()
    {
        return [
            [1, '1 must be a string'],
            [0, '0 must be a string'],
            [true, 'true must be a string'],
            [false, 'false must be a string'],
            [[], '[] must be a string'],
            [1.00, '1 must be a string'],
            [123, '123 must be a string'],
            [100, '100 must be a string'],
            [0.1, '0.1 must be a string'],
            [05.00, '5 must be a string'],
        ];
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToBTC
     *
     * @dataProvider dataStringToXTypeExceptions
     * @group stable
     *
     * @param mixed  $notString
     *   Thing that is not a string.
     *
     * @param string $message
     *   The expected exception.
     */
    public function testStringToBTCTypeExceptions($notString, $message)
    {
        $this->setExpectedException('Exception', $message);

        MoneyStringsUtil::stringToBTC($notString);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToBTC
     *
     * @group stable
     */
    public function testStringToBTCNullException()
    {
        $this->setExpectedException('Exception', 'null must be a string');

        MoneyStringsUtil::stringToBTC(null);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToUSD
     *
     * @dataProvider dataStringToXTypeExceptions
     * @group stable
     *
     * @param mixed  $notString
     *   Thing that is not a string.
     *
     * @param string $message
     *   The expected exception message.
     */
    public function testStringToUSDTypeExceptions($notString, $message)
    {
        $this->setExpectedException('Exception', $message);

        MoneyStringsUtil::stringToUSD($notString);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToUSD
     *
     * @group stable
     */
    public function testStringToUSDNullException()
    {
        $this->setExpectedException('Exception', 'null must be a string');

        MoneyStringsUtil::stringToUSD(null);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::btcToString
     *
     * @group stable
     */
    public function testBtcToString()
    {
        $tests = [
            ['0.00000001', Money::BTC(1)],
            ['0.00000010', Money::BTC(10)],
            ['0.00000100', Money::BTC(100)],
            ['0.00001000', Money::BTC(1000)],
            ['0.00010000', Money::BTC(10000)],
            ['0.00100000', Money::BTC(100000)],
            ['0.01000000', Money::BTC(1000000)],
            ['0.10000000', Money::BTC(10000000)],
            ['1.00000000', Money::BTC(100000000)],
            ['1.23456789', Money::BTC(123456789)],
            ['12.34567891', Money::BTC(1234567891)],
        ];

        foreach ($tests as $test) {
            $this->assertSame($test[0], MoneyStringsUtil::btcToString($test[1]));
        }
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::usdToString
     *
     * @group stable
     */
    public function testUsdToString()
    {
        $tests = [
            ['0.01', Money::USD(1)],
            ['0.10', Money::USD(10)],
            ['1.00', Money::USD(100)],
            ['1.23', Money::USD(123)],
            ['12.34', Money::USD(1234)],
        ];

        foreach ($tests as $test) {
            $this->assertSame($test[0], MoneyStringsUtil::usdToString($test[1]));
        }
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToUSD
     *
     * @group stable
     */
    public function testStringToUSD()
    {
        $tests = [
            [Money::USD(10000), '$100'],
            [Money::USD(10000), '$$100'],
            [Money::USD(10000), '100'],
            [Money::USD(10000), '100.0'],
            [Money::USD(10000), '100.00'],
            [Money::USD(100), '1.00'],
            [Money::USD(1), '0.01'],
            [Money::USD(1), '.01'],
            [Money::USD(123), '1.23'],
            [Money::USD(123), '1.234'],
            [Money::USD(124), '1.235'],
        ];

        foreach ($tests as $test) {
            $this->assertEquals($test[0], MoneyStringsUtil::stringToUSD($test[1]));
        }
    }

    /**
     * Data provider for testStringToUSD() and base of others.
     *
     * @see dataStringToBTCExceptions()
     *
     * @return string[][]
     */
    public function dataStringToXExceptions()
    {
        return [
            ['a1', '"a1" must be numeric'],
            ['1a', '"1a" must be numeric'],
            ['1,00', '"1,00" must be numeric'],
            ['1,000.00', '"1,000.00" must be numeric'],
        ];
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToUSD
     *
     * @dataProvider dataStringToXExceptions
     * @group stable
     *
     * @param string $string
     *   The string to test.
     *
     * @param string $message
     *   The expected exception message.
     */
    public function testStringToUSDExceptions($string, $message)
    {
        $this->setExpectedException('Exception', $message);

        MoneyStringsUtil::stringToUSD($string);
    }

    /**
     * Data provider for testStringToBTC
     *
     * @return array[]
     */
    public function dataStringToBTC()
    {
        $data = [
            [1, '.00000001'],
            [1, '0.00000001'],
            [10, '0.0000001'],
            [100, '0.000001'],
            [1000, '0.00001'],
            [10000, '0.0001'],
            [100000, '0.001'],
            [1000000, '0.01'],
            [10000000, '0.1'],
            [100000000, '1'],
            [100000000, '1.'],
            [100000000, '1.0'],
            [123456789, '1.23456789'],
            [123456790, '1.234567899'],
            [123456789, '1.234567893'],
        ];

        return array_map(function($test) {
            return [Money::BTC($test[0]), $test[1]];
        }, $data);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToBTC
     *
     * @dataProvider dataStringToBTC
     *
     * @param Money  $btc
     *   The expected BTC.
     *
     * @param string $string
     *   The string that should result in the expected BTC.
     *
     * @group stable
     */
    public function testStringToBTC(Money $btc, $string)
    {
        v::string()->check($string);

        $this->assertEquals($btc, MoneyStringsUtil::stringToBTC($string));
    }

    /**
     * Data provider for testStringToBTC().
     *
     * @return string[][]
     */
    public function dataStringToBTCExceptions()
    {
        return array_merge($this->dataStringToXExceptions(), [['$100', '"$100" must be numeric']]);
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToBTC
     *
     * @dataProvider dataStringToBTCExceptions
     * @group stable
     *
     * @param string $string
     *   The string to test.
     *
     * @param string $message
     *   The expected exception message.
     */
    public function testStringToBTCExceptions($string, $message)
    {
        $this->setExpectedException('Exception', $message);
        MoneyStringsUtil::stringToBTC($string);
    }
}
