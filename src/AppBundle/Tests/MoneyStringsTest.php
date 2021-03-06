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
     * Data provider for testBtcToString
     *
     * @return array[]
     */
    public function dataBtcToString()
    {
        return [
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
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::btcToString
     *
     * @dataProvider dataBtcToString
     *
     * @param string $string
     * @param Money  $btc
     *
     * @group stable
     */
    public function testBtcToString($string, Money $btc)
    {
        v::stringType()->check($string);

        $this->assertSame($string, MoneyStringsUtil::btcToString($btc));
    }

    /**
     * Data provider for testUsdToString
     *
     * @return array[]
     */
    public function dataUsdToString()
    {
        return [
            ['0.01', Money::USD(1)],
            ['0.10', Money::USD(10)],
            ['1.00', Money::USD(100)],
            ['1.23', Money::USD(123)],
            ['12.34', Money::USD(1234)],
        ];
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::usdToString
     *
     * @dataProvider dataUsdToString
     *
     * @param string $string
     * @param Money  $usd
     *
     * @group stable
     */
    public function testUsdToString($string, Money $usd)
    {
        v::stringType()->check($string);

        $this->assertSame($string, MoneyStringsUtil::usdToString($usd));
    }

    /**
     * Data provider for testStringToUSD
     *
     * @return array[]
     */
    public function dataStringToUSD()
    {
        return [
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
    }

    /**
     * @covers AppBundle\MoneyStringsUtil::stringToUSD
     *
     * @dataProvider dataStringToUSD
     *
     * @param Money  $usd
     *   The expected USD.
     *
     * @param string $string
     *   The string that should result in the expected USD.
     *
     * @group stable
     */
    public function testStringToUSD(Money $usd, $string)
    {
        v::stringType()->check($string);

        $this->assertEquals($usd, MoneyStringsUtil::stringToUSD($string));
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
        return [
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
        v::stringType()->check($string);

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
