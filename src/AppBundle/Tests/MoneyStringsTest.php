<?php

namespace AppBundle\Tests;

use AppBundle\MoneyStrings;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for AppBundle\MoneyStrings
 */
class MoneyStringsTest extends WebTestCase
{
    /**
     * Data provider for testStringToUSDTypeExceptions().
     *
     * @return array
     */
    public function dataStringToXTypeExceptions()
    {
        return [
            [1, '1 is not a string.'],
            [0, '0 is not a string.'],
            [true, 'true is not a string.'],
            [false, 'false is not a string.'],
            [[], '[] is not a string.'],
            [1.00, '1 is not a string.'],
            [123, '123 is not a string.'],
            [100, '100 is not a string.'],
            [0.1, '0.1 is not a string.'],
            [05.00, '5 is not a string.'],
        ];
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToBTC
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
        MoneyStrings::stringToBTC($notString);
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToBTC
     *
     * @expectedException Exception
     * @expectedExceptionMessage null is not a string.
     * @group stable
     */
    public function testStringToBTCNullException()
    {
        MoneyStrings::stringToBTC(null);
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToUSD
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
        MoneyStrings::stringToUSD($notString);
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToUSD
     *
     * @expectedException Exception
     * @expectedExceptionMessage null is not a string.
     * @group stable
     */
    public function testStringToUSDNullException()
    {
        MoneyStrings::stringToUSD(null);
    }

    /**
     * @covers AppBundle\MoneyStrings::BTCToString
     *
     * @group stable
     */
    public function testBTCToString()
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
            $this->assertSame($test[0], MoneyStrings::BTCToString($test[1]));
        }
    }

    /**
     * @covers AppBundle\MoneyStrings::USDToString
     *
     * @group stable
     */
    public function testUSDToString()
    {
        $tests = [
            ['0.01', Money::USD(1)],
            ['0.10', Money::USD(10)],
            ['1.00', Money::USD(100)],
            ['1.23', Money::USD(123)],
            ['12.34', Money::USD(1234)],
        ];

        foreach ($tests as $test) {
            $this->assertSame($test[0], MoneyStrings::USDToString($test[1]));
        }
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToUSD
     *
     * @group stable
     */
    public function testStringToUSD()
    {
        $tests =[
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
            $this->assertEquals($test[0], MoneyStrings::stringToUSD($test[1]));
        }
    }

    public function dataStringToXExceptions()
    {
        return [
            ['a1', '"a1" is not numeric.'],
            ['1a', '"1a" is not numeric.'],
            ['1,00', '"1,00" is not numeric.'],
            ['1,000.00', '"1,000.00" is not numeric.'],
        ];
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToUSD
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
        MoneyStrings::stringToUSD($string);
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToBTC
     *
     * @group stable
     */
    public function testStringToBTC()
    {
        $tests = [
            [Money::BTC(1), '.00000001'],
            [Money::BTC(1), '0.00000001'],
            [Money::BTC(10), '0.0000001'],
            [Money::BTC(100), '0.000001'],
            [Money::BTC(1000), '0.00001'],
            [Money::BTC(10000), '0.0001'],
            [Money::BTC(100000), '0.001'],
            [Money::BTC(1000000), '0.01'],
            [Money::BTC(10000000), '0.1'],
            [Money::BTC(100000000), '1'],
            [Money::BTC(100000000), '1.'],
            [Money::BTC(100000000), '1.0'],
            [Money::BTC(123456789), '1.23456789'],
            [Money::BTC(123456790), '1.234567899'],
            [Money::BTC(123456789), '1.234567893'],
        ];

        foreach ($tests as $test) {
            $this->assertEquals($test[0], MoneyStrings::stringToBTC($test[1]));
        }
    }

    /**
     * Data provider for testStringToBTC().
     *
     * @return array
     */
    public function dataStringToBTCExceptions()
    {
        return array_merge($this->dataStringToXExceptions(), [['$100', '"$100" is not numeric.']]);
    }

    /**
     * @covers AppBundle\MoneyStrings::stringToBTC
     *
     * @dataProvider dataStringToBTCExceptions
     * @group stable
     *
     * @param string $string
     *   The string to test.
     */
    public function testStringToBTCExceptions($string, $message)
    {
        $this->setExpectedException('Exception', $message);
        MoneyStrings::stringToBTC($string);
    }
}
