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
     * @covers AppBundle\Ensure::toInt
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
}
