<?php

namespace AppBundle\Tests;

use AppBundle\Ensure;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests \AppBundle\Ensure
 */
class EnsureTest extends WebTestCase
{
    /**
     * Data provider for isNumeric.
     *
     * @return array
     */
    public function dataIsNumericExceptions()
    {
        return [
            ['foo', '"foo" is not numeric'],
            [null, 'null is not numeric'],
            [[], '[] is not numeric'],
            [new \StdClass(), '{} is not numeric'],
            ['', '"" is not numeric'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::isNumeric
     *
     * @param mixed  $value
     *   Not a number.
     *
     * @param string $message
     *   The exception message.
     *
     * @group stable
     *
     * @dataProvider dataIsNumericExceptions
     */
    public function testIsNumericExceptions($value, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isNumeric($value);
    }

    /**
     * @covers AppBundle\Ensure::isNumeric
     *
     * @group stable
     */
    public function testIsNumeric()
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
            $this->assertSame($test, Ensure::isNumeric($test));
        });
    }

    /**
     * Data provider for testLessThanExceptions
     *
     * @group stable
     *
     * @return array
     */
    public function dataLessThanExceptions()
    {
        return [
            // ints.
            [1, 0, '1 is not less than 0.'],
            [0, -1, '0 is not less than -1.'],
            [2, 1, '2 is not less than 1.'],
            [1, -1, '1 is not less than -1.'],
            // floats.
            [1.1, 0.1, '1.1 is not less than 0.1.'],
            [0.1, -1.1, '0.1 is not less than -1.1.'],
            [2.1, 1.1, '2.1 is not less than 1.1.'],
            [1.1, -1.1, '1.1 is not less than -1.1.'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::lessThan
     *
     * @param number $small
     *   Not a small number.
     *
     * @param number $big
     *   Not a big number.
     *
     * @param string $message
     *   Expected exception message.
     *
     * @group stable
     *
     * @dataProvider dataLessThanExceptions
     */
    public function testLessThanExceptions($small, $big, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::lessThan($small, $big);
    }

    /**
     * @covers AppBundle\Ensure::lessThan
     *
     * @group stable
     */
    public function testLessThan()
    {
        $tests = [
            [1, 2],
            [0, 1],
            [-0, 1],
            [-1, 0],
            [-1, -0],
            [-1, 1],
        ];
        array_walk($tests, function($test) {
            // Check that this works for floats and ints.
            $this->assertSame((int) $test[0], Ensure::lessThan((int) $test[0], (int) $test[1]));
            $this->assertSame((float) $test[0], Ensure::lessThan((float) $test[0], (float) $test[1]));
        });

        $funStuff = [
            [null, true],
            [false, true],
        ];
        array_walk($funStuff, function($test) {
            $this->assertSame($test[0], Ensure::lessThan($test[0], $test[1]));
        });
    }

    /**
     * Data provider for testIsValidVariableNameExceptions
     *
     * @group stable
     *
     * @return array
     */
    public function dataIsValidVariableNameExceptions()
    {
        return [
            ['1', '"1" is not a valid variable name.'],
            ['-', '"-" is not a valid variable name.'],
            ['@', '"@" is not a valid variable name.'],
            ['1o', '"1o" is not a valid variable name.'],
            ['foo-bar', '"foo-bar" is not a valid variable name.'],
            [1, '1 is not a string.'],
            [[], '[] is not a string.'],
            [null, 'null is not a string.'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::isValidVariableName
     *
     * @param mixed  $name
     *   Anything that is not a valid variable name.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @dataProvider dataIsValidVariableNameExceptions
     *
     * @group stable
     */
    public function testIsValidVariableNameExceptions($name, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isValidVariableName($name);
    }

    /**
     * @covers AppBundle\Ensure::isValidVariableName
     *
     * @group stable
     */
    public function testIsValidVariableName()
    {
        $tests = [uniqid('a'), 'with_underscore', 'one1', 'a' . md5(uniqid())];
        array_walk($tests, function($test) {
            $this->assertSame($test, Ensure::isValidVariableName($test));
        });
    }

    /**
     * Data provider.
     *
     * Things that are not strings.
     *
     * @return array
     */
    public function dataIsStringExceptions()
    {
        return [
            [[], '[] is not a string.'],
            [1, '1 is not a string.'],
            [null, 'null is not a string.'],
            [new \StdClass(), '{} is not a string.'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::isString
     *
     * @param mixed  $value
     *   Anything that is not a string.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @dataProvider dataIsStringExceptions
     *
     * @group stable
     */
    public function testIsStringExceptions($value, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isString($value);
    }
    /**
     * @covers AppBundle\Ensure::isString
     *
     * @group stable
     */
    public function testIsString()
    {
        $tests = ['', '1', 'foo', (string) mt_rand()];
        array_walk($tests, function($test) {
            $this->assertSame($test, Ensure::isString($test));
        });
    }
    /**
     * @covers AppBundle\Ensure::notNull
     *
     * @group stable
     */
    public function testNotNull()
    {
        // Everything is set... try a few things.
        $tests = [
            'foo',
            '',
            1,
            '1',
            0,
            new \DateTime(),
            [],
        ];
        array_walk($tests, function($test) {
            $this->assertSame($test, Ensure::notNull($test));
        });
    }

    /**
     * @covers AppBundle\Ensure::notNull
     *
     * @group stable
     */
    public function testNotNullExceptions()
    {
        $this->setExpectedException('Exception', 'null is not set.');
        Ensure::notNull(null);
    }

    /**
     * Data provider for testIsEmptyExceptions().
     *
     * @return array
     */
    public function dataIsEmptyExceptions()
    {
        return [
        [1, '1 is not empty.'],
        ['1', '"1" is not empty.'],
        [[null], '[null] is not empty.'],
        [new \StdClass(), '{} is not empty.'],
        ];
    }

    /**
     * Data provider for testNotEmptyExceptions
     *
     * @return array
     */
    public function dataNotEmptyExceptions()
    {
        return [
        ['', '"" is empty.'],
        [0, '0 is empty.'],
        ['0', '"0" is empty.'],
        [[], '[] is empty.'],
        [null, 'null is empty.'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::isEmpty
     *
     * @param empty  $empty
     *   Anything empty.
     *
     * @param string $message
     *   The expected exception message (ignored in this test).
     *
     * @dataProvider dataNotEmptyExceptions
     * @group stable
     */
    public function testIsEmpty($empty, $message)
    {
        $this->assertSame($empty, Ensure::isEmpty($empty));
    }

    /**
     * @covers AppBundle\Ensure::isEmpty
     *
     * @param mixed  $notEmpty
     *   Anything not empty.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @dataProvider dataIsEmptyExceptions
     * @group stable
     */
    public function testIsEmptyExceptions($notEmpty, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isEmpty($notEmpty);
    }

    /**
     * @covers AppBundle\Ensure::notEmpty
     *
     * @param mixed  $notEmpty
     *   Anything not empty.
     *
     * @param string $message
     *   The expected expection message (ignored in this test).
     *
     * @dataProvider dataIsEmptyExceptions
     * @group stable
     */
    public function testNotEmpty($notEmpty, $message)
    {
        $this->assertSame($notEmpty, Ensure::notEmpty($notEmpty));
    }

    /**
     * @covers AppBundle\Ensure::notEmpty
     *
     * @param empty  $empty
     *   Anything empty.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @dataProvider dataNotEmptyExceptions
     * @group stable
     */
    public function testNotEmptyExceptions($empty, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::notEmpty($empty);
    }

    /**
     * @covers AppBundle\Ensure::toInt
     *
     * @group stable
     */
    public function testToInt()
    {
        $i = -5;
        while ($i <= 5) {
            $this->assertSame($i, Ensure::toInt($i));
            $this->assertSame($i, Ensure::toInt((string) $i));
            $i++;
        }
        $this->assertSame(PHP_INT_MAX, Ensure::toInt(PHP_INT_MAX));
    }

    /**
     * @covers AppBundle\Ensure::inRange
     *
     * @group stable
     */
    public function testInRange()
    {
        $tests = [
        [0, -1, 1],
        [2, 1, 3],
        [1.5, 1, 2],
        // Ranges can go backwards too.
        [1.5, 2, 1],
        [2, 3, 1],
        [0, 1, -1],
        ];
        array_walk($tests, function($test) {
            $this->assertSame($test[0], Ensure::inRange($test[0], $test[1], $test[2]));
        });
    }

    /**
     * Data provider for testInRangeExceptions.
     *
     * @return array
     */
    public function dataInRangeExceptions()
    {
        return [
        [-1, 1, 0, '-1 is not in the range of 0 and 1.'],
        [1, 3, 2, '1 is not in the range of 2 and 3.'],
        [1, 2, 1.5, '1 is not in the range of 1.5 and 2.'],
        [2, 1, 1.5, '2 is not in the range of 1 and 1.5.'],
        [3, 2, 1, '3 is not in the range of 1 and 2.'],
        [1, -1, 0, '1 is not in the range of -1 and 0.'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::inRange
     *
     * @param number $value
     *   A number in range.
     *
     * @param number $boundOne
     *   The first range bound.
     *
     * @param number $boundTwo
     *   The second range bound.
     *
     * @param string $message
     *   The expected exception message for out of range.
     *
     * @dataProvider dataInRangeExceptions
     * @group stable
     */
    public function testInRangeExceptions($value, $boundOne, $boundTwo, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::inRange($value, $boundOne, $boundTwo);
    }

    /**
     * @covers AppBundle\Ensure::isInstanceOf
     *
     * @group stable
     */
    public function testIsInstanceOf()
    {
        $tests = [
        [new \DateTime(), '\DateTime'],
        [new \StdClass(), '\StdClass'],
        ];
        array_walk($tests, function($test) {
            $this->assertSame($test[0], Ensure::isInstanceOf($test[0], $test[1]));
        });
    }

    /**
     * Data provider for testIsInstanceOfExceptions().
     *
     * @return array
     */
    public function dataIsInstanceOfExceptions()
    {
        return [
        [new \StdClass(), '\DateTime', '{} is not an instance of "\\\DateTime".'],
        [[], '\StdClass', '[] is not an instance of "\\\StdClass".'],
        [null, '\StdClass', 'null is not an instance of "\\\StdClass".'],
        ];
    }

    /**
     * @covers AppBundle\Ensure::isInstanceOf
     *
     * @param mixed  $value
     *   The value to check against instance of $class.
     *
     * @param string $class
     *   A class that $value is not.
     *
     * @param string $message
     *   The message to expect in the exception thrown.
     *
     * @dataProvider dataIsInstanceOfExceptions
     * @group stable
     */
    public function testIsInstanceOfExceptions($value, $class, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isInstanceOf($value, $class);
    }

    /**
     * @covers AppBundle\Ensure::fail
     *
     * @group stable
     */
    public function testFail()
    {
        $this->setExpectedException('Exception', '"foo" is "bar", but "bing" too! [], "", 1');
        Ensure::fail('%s is %s, but %s too! %s, %s, %s', 'foo', 'bar', 'bing', [], '', 1);
    }
}
