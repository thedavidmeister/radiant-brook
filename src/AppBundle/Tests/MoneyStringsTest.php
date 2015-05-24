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
            [1],
            [0],
            [true],
            [false],
            [[]],
            [1.00],
            [123],
            [100],
            [0.1],
            [05.00],
        ];
    }

    /**
     * Tests that exceptions are thrown when stringToBTC does not get a string.
     *
     * @dataProvider dataStringToXTypeExceptions
     * @expectedException Exception
     * @expectedExceptionMessage The parameter passed to stringToBTC must be a string
     * @group stable
     *
     * @param mixed $notString
     */
    public function testStringToBTCTypeExceptions($notString)
    {
        MoneyStrings::stringToBTC($notString);
    }

    /**
     * Tests that exceptions are thrown when stringToBTC gets null.
     *
     * @expectedException Exception
     * @expectedExceptionMessage The parameter passed to stringToBTC must be a string
     * @group stable
     */
    public function testStringToBTCNullException()
    {
        MoneyStrings::stringToBTC(null);
    }

    /**
     * Tests that exceptions are thrown when stringToUSD does not get a string.
     *
     * @dataProvider dataStringToXTypeExceptions
     * @expectedException Exception
     * @expectedExceptionMessage The parameter passed to stringToUSD must be a string
     * @group stable
     *
     * @param mixed $notString
     *   Thing that is not a string.
     */
    public function testStringToUSDTypeExceptions($notString)
    {
        MoneyStrings::stringToUSD($notString);
    }

    /**
     * Tests that exceptions are thrown when stringToUSD gets null.
     *
     * @expectedException Exception
     * @expectedExceptionMessage The parameter passed to stringToUSD must be a string
     * @group stable
     */
    public function testStringToUSDNullException()
    {
        MoneyStrings::stringToUSD(null);
    }

    /**
     * Tests MoneyString::BTCToString().
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
     * Tests MoneyStrings::USDToString().
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
     * Tests MoneyStrings::stringToUSD().
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

    /**
     * Data provider for testStringToUSD().
     *
     * @return array
     */
    public function dataStringToUSDExceptions()
    {
        return [
            ['a1'],
            ['1a'],
            ['1,00'],
            ['1,000.00'],
        ];
    }

    /**
     * Tests stringToUSD exceptions.
     *
     * @dataProvider dataStringToUSDExceptions
     * @expectedException Exception
     * @expectedExceptionMessage Could not parse Money::USD from string:
     * @group stable
     *
     * @param string $string
     *   The string to test.
     */
    public function testStringToUSDExceptions($string)
    {
        MoneyStrings::stringToUSD($string);
    }

    /**
     * Tests MoneyStrings::stringToBTC().
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
        return [
            ['a1'],
            ['1a'],
            ['1,00'],
            ['1,000.00'],
            ['$100'],
        ];
    }

    /**
     * Tests exceptions thrown by stringToBTC().
     *
     * @dataProvider dataStringToBTCExceptions
     * @expectedException Exception
     * @expectedExceptionMessage Could not parse Money::BTC from string:
     * @group stable
     *
     * @param string $string
     *   The string to test.
     */
    public function testStringToBTCExceptions($string)
    {
        MoneyStrings::stringToBTC($string);
    }
}
