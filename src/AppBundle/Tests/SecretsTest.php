<?php

namespace AppBundle\Tests;

use AppBundle\Secrets;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests \AppBundle\Secrets
 */
class SecretsTest extends WebTestCase
{
    protected function secrets()
    {
        return new Secrets();
    }

    protected function key()
    {
        return strtoupper(uniqid('k'));
    }

    protected function value()
    {
        return uniqid('v');
    }

    /**
     * @covers AppBundle\Secrets::clear
     *
     * @see testSet()
     *
     * @group stable
     */
    public function testClear()
    {
        $key = $this->key();
        $value = $this->value();
        $this->secrets()->set($key, $value);
        $this->secrets()->clear($key);

        $this->assertSame(false, getenv($key));
        $this->assertTrue(empty($_SERVER[$key]));
        $this->assertTrue(empty($_ENV[$key]));

        $this->setExpectedException('Exception', 'Loading .env file failed while attempting to access environment variable ' . $key);
        $this->secrets()->get($key);
    }

    /**
     * @covers AppBundle\Secrets::dotEnvPath
     *
     * @group stable
     */
    public function testDotEnvPath()
    {
        // We expect the Secrets class to be just above this directory.
        $expected = str_replace('/Tests', '', __DIR__);
        $this->assertSame($expected, $this->secrets()->dotEnvPath());
    }

    /**
     * @covers AppBundle\Secrets::set
     * @covers AppBundle\Secrets::get
     *
     * @group stable
     *
     * @return void
     */
    public function testSet()
    {
        $tests = [
            function(array $tuple) {
                $this->secrets()->set($tuple[0], $tuple[1]);
            },
            function(array $tuple) {
                putenv($tuple[0] . '=' . $tuple[1]);
            },
            function(array $tuple) {
                $_ENV[$tuple[0]] = $tuple[1];
            },
            function(array $tuple) {
                $_SERVER[$tuple[0]] = $tuple[1];
            },
        ];
        array_walk($tests, function (callable $setFunc) {
            // Generate a unique tuple that will not collide with previous sets.
            $tuple = [uniqid('a'), uniqid('a')];
            $setFunc($tuple);
            $this->assertSame($tuple[1], $this->secrets()->get($tuple[0]));
        });
    }

    /**
     * Data provider for testSetValueExceptions.
     *
     * @return array
     */
    public function dataSetValueExceptions()
    {
        return [
            [1, '1 must be a string'],
            [0, '0 must be a string'],
            [(float) 1, '1 must be a string'],
            [(float) 0, '0 must be a string'],
            [null, 'null must be a string'],
            [[], '{ } must be a string'],
            [(object) [], '`[object] (stdClass: { })` must be a string'],
        ];
    }

    /**
     * @covers AppBundle\Secrets::set
     *
     * @dataProvider dataSetValueExceptions
     *
     * @param mixed  $value
     *   Things that are not valid values for Secrets to set.
     *
     * @param string $message
     *   The expected exception message.
     *
     * @group stable
     */
    public function testSetValueExceptions($value, $message)
    {
        $this->setExpectedException('Exception', $message);

        $this->secrets()->set(uniqid('a'), $value);
    }

    /**
     * Data provider for testSetNameExceptions
     *
     * @return array
     */
    public function dataNameExceptions()
    {
        return [
            ['1', '"1" must be a valid PHP label'],
            ['-', '"-" must be a valid PHP label'],
            ['@', '"@" must be a valid PHP label'],
            ['1o', '"1o" must be a valid PHP label'],
            ['foo-bar', '"foo-bar" must be a valid PHP label'],
            [1, '1 must be a valid PHP label'],
            [[], '{ } must be a valid PHP label'],
            [null, 'null must be a valid PHP label'],
        ];
    }

    /**
     * @covers AppBundle\Secrets::get
     *
     * @dataProvider dataNameExceptions
     *
     * @param mixed  $name
     *   Things that are not valid variable names to get.
     *
     * @param string $message
     *   The exception message to throw.
     *
     * @group stable
     */
    public function testGetNameExceptions($name, $message)
    {
        $this->setExpectedException('Exception', $message);

        $this->secrets()->get($name);
    }

    /**
     * @covers AppBundle\Secrets::clear
     *
     * @dataProvider dataNameExceptions
     *
     * @param mixed  $name
     *   Things that are not valid variable names to clear.
     *
     * @param string $message
     *   The exception message to throw.
     *
     * @group stable
     */
    public function testClearNameExceptions($name, $message)
    {
        $this->setExpectedException('Exception', $message);

        $this->secrets()->clear($name);
    }

    /**
     * @covers AppBundle\Secrets::set
     *
     * @dataProvider dataNameExceptions
     *
     * @param mixed  $name
     *   Things that are not valid variable names to set.
     *
     * @param string $message
     *   The exception message to throw.
     *
     * @group stable
     */
    public function testSetNameExceptions($name, $message)
    {
        $this->setExpectedException('Exception', $message);

        // The value can be anything, we just want the name to throw.
        $this->secrets()->set($name, mt_rand());
    }

    /**
     * @covers AppBundle\Secrets::get
     *
     * @expectedException Exception
     * @expectedExceptionMessage Loading .env file failed while attempting to access environment variable NO_MATCH
     * @group stable
     */
    public function testGetException()
    {
        $this->secrets()->get('NO_MATCH');
    }
}
