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
    * Test set().
    *
    * @group stable
    */
    public function testSet()
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
            $this->assertSame($test, Ensure::set($test));
        });
    }

    /**
    * Test exceptions from set().
    *
    * @group stable
    */
    public function testSetExceptions()
    {
        $this->setExpectedException('Exception', 'null is not set.');
        Ensure::set(null);
    }

    /**
   * Data provider for testIsEmptyExceptions().
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
   * Test isEmpty().
   *
   * @dataProvider dataNotEmptyExceptions
   * @group stable
   */
    public function testIsEmpty($empty, $message)
    {
        $this->assertSame($empty, Ensure::isEmpty($empty));
    }

    /**
   * Test exceptions from isEmpty().
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
   * Test notEmpty().
   *
   * @dataProvider dataIsEmptyExceptions
   * @group stable
   */
    public function testNotEmpty($notEmpty, $message)
    {
        $this->assertSame($notEmpty, Ensure::notEmpty($notEmpty));
    }

    /**
   * Test exceptions for notEmpty().
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
   * Test isInt().
   *
   * @group stable
   */
    public function testIsInt()
    {
        $i = -5;
        while ($i <= 5) {
            $this->assertSame($i, Ensure::isInt($i));
            $this->assertSame($i, Ensure::isInt((string) $i));
            $i++;
        }
        $this->assertSame(PHP_INT_MAX, Ensure::isInt(PHP_INT_MAX));
    }

    /**
   * Data provider for dataIsIntExceptions
   */
    public function dataIsIntExceptions()
    {
        return [
        [1.1, '1.1 is not an int.'],
        ['foo', '"foo" is not an int.'],
        [null, 'null is not an int.'],
        [[], '[] is not an int.'],
        ];
    }

    /**
   * Test exceptions for isInt().
   *
   * @dataProvider dataIsIntExceptions
   * @group stable
   */
    public function testIsIntExceptions($notInt, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::isInt($notInt);
    }

    /**
   * Tests inRange().
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
   * Tests exceptions for inRange().
   *
   * @dataProvider dataInRangeExceptions
   * @group stable
   */
    public function testInRangeExceptions($value, $bound_one, $bound_two, $message)
    {
        $this->setExpectedException('Exception', $message);
        Ensure::inRange($value, $bound_one, $bound_two);
    }

    /**
   * Tests isInstanceOf().
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

    public function dataIsInstanceOfExceptions()
    {
        return [
        [new \StdClass(), '\DateTime', '{} is not an instance of "\\\DateTime".'],
        [[], '\StdClass', '[] is not an instance of "\\\StdClass".'],
        [null, '\StdClass', 'null is not an instance of "\\\StdClass".'],
        ];
    }

    /**
   * Tests exceptions for isInstanceOf().
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
   * Tests exceptions thrown by fail().
   *
   * @group stable
   */
    public function testFail()
    {
        $this->setExpectedException('Exception', '"foo" is "bar", but "bing" too! [], "", 1');
        Ensure::fail('%s is %s, but %s too! %s, %s, %s', 'foo', 'bar', 'bing', [], '', 1);
    }
}
