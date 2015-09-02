<?php

namespace AppBundle\Tests;

use AppBundle\Cast;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests \AppBundle\Cast
 */
class CastTest extends WebTestCase
{
    /**
     * Test that boolean-y things cast to corresponding boolean values.
     */
    public function testToBoolean()
    {
        $tests = [
            [true, true],
            ['true', true],
            [1, true],
            ['yes', true],
            [false, false],
            // This casts to false because of boolean-y rules. Probably the
            // biggest surprise here as normal string conversion casts it to
            // true.
            ['false', false],
            [0, false],
            ['no', false],
            ['', false],
        ];

        array_walk($tests, function($test) {
            list($value, $expected) = $test;
            $this->assertSame($expected, Cast::toBoolean($value), 'Test does not cast to ' . $expected . '. Value: ' . json_encode($value));
        });

        $tests = [true, false];
        array_walk($tests, function ($boolean) {
            $booleanyObject = new Mocks\BooleanyObject($boolean);
            $result = Cast::toBoolean($booleanyObject);
            $this->assertSame($boolean, $result, 'BooleanyObject is not ' . json_encode($boolean) . '. String: ' . $booleanyObject);
        });
    }

    /**
     * Data provider for dataToIntExceptions
     *
     * @return array
     */
    public function dataToIntExceptions()
    {
        return [
            [1.1, '1.1 is not an int.'],
            ['foo', '"foo" is not an int.'],
            [null, 'null is not an int.'],
            [[], '[] is not an int.'],
            [true, 'true is not an int.'],
            [false, 'false is not an int.'],
            // Negative scientific notation is not an int.
            [1e-1, '0.1 is not an int.'],
            [1e-2, '0.01 is not an int.'],
        ];
    }

    /**
     * @covers AppBundle\Cast::toInt
     *
     * @param mixed  $notInt
     *   Not an integer.
     *
     * @param string $message
     *   The message to expect in the exception.
     *
     * @dataProvider dataToIntExceptions
     * @group stable
     */
    public function testToIntExceptions($notInt, $message)
    {
        $this->setExpectedException('Exception', $message);
        Cast::toInt($notInt);
    }

    /**
     * @covers AppBundle\Cast::toInt
     *
     * @group stable
     */
    public function testToInt()
    {
        // expected, test.
        $tests = [
            // Basics.
            [-2, -2],
            [-2, (float) -2],
            [-2, (string) -2],
            [-1, -1],
            [-1, (float) -1],
            [-1, (string) -1],
            [0, -0],
            [0, (float) -0],
            [0, (string) -0],
            [0, 0],
            [0, (float) 0],
            [1, 1],
            [1, (float) 1],
            [2, 2],
            [2, (float) 2],
            [2, (string) 2],
            // Get some scientific notation happening.
            [1, 1e0],
            [10, 1e1],
            [10, 10e0],
            [100, 10e1],
            [1, 1e-0],
            [10, 10e-0],
            [1, 1e-00],
            [10, 10e-00],
            [1000, 10e2],
            // Extremes. PHP does weird shit out of bounds.
            // @todo [-PHP_INT_MAX - 1, (string) (-PHP_INT_MAX - 2)],
            // and all other string weirdness.
            [-PHP_INT_MAX - 1, -PHP_INT_MAX - 2],
            [-PHP_INT_MAX - 1, -PHP_INT_MAX - 1],
            [-PHP_INT_MAX, -PHP_INT_MAX],
            [-PHP_INT_MAX + 1, -PHP_INT_MAX + 1],
            [PHP_INT_MAX - 1, PHP_INT_MAX - 1],
            [PHP_INT_MAX, PHP_INT_MAX],
        ];
        array_walk($tests, function($test) {
            $this->assertTrue(is_int(Cast::toInt($test[1])));
            $this->assertSame($test[0], Cast::toInt($test[1]));
        });
    }

    /**
     * Data provider for testToFloatExceptions.
     *
     * @return array
     */
    public function dataToFloatExceptions()
    {
        return [
            [true, 'true is not numeric.'],
            [false, 'false is not numeric.'],
            ['foo', '"foo" is not numeric'],
            [null, 'null is not numeric.'],
            [[], '[] is not numeric.'],
            [new \StdClass(), '{} is not numeric.'],
            ['', '"" is not numeric.'],
        ];
    }

    /**
     * @covers AppBundle\Cast::toFloat
     *
     * @param mixed  $value
     *   Not a number.
     *
     * @param string $message
     *   The exception message.
     *
     * @dataProvider dataToFloatExceptions
     *
     * @group stable
     */
    public function testToFloatExceptions($value, $message)
    {
        $this->setExpectedException('Exception', $message);
        Cast::toFloat($value);
    }

    /**
     * @covers AppBundle\Cast::toFloat
     *
     * @group stable
     */
    public function testToFloat()
    {
        $tests = [
        // Int.
        mt_rand(),
        // Float.
        1.5,
        0.1,
        -5.0,
        // Numeric string.
        '1',
        '1.5',
        '-5.0',
        '-5',
        ];
        array_walk($tests, function($test) {
            $this->assertSame((float) $test, Cast::toFloat($test));
        });
    }
}
