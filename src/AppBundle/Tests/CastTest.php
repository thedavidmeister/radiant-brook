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
            [true, 'true is not a number.'],
            [false, 'false is not a number.'],
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
     * Data provider for testToFloatExceptions.
     *
     * @return array
     */
    public function dataToFloatExceptions()
    {
        return [
            [true, 'true is not a number.'],
            [false, 'false is not a number.'],
            [[], '[] is not a number.'],
            [\StdClass(), '{} is not a number.'],
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
     * @dataProvider dataIsNumericExceptions
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
