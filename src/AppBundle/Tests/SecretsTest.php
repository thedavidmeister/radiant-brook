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

    /**
     * @covers AppBundle\Secrets::set
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
     * @covers AppBundle\Secrets::get
     *
     * @expectedException Exception
     * @expectedExceptionMessage Loading .env file failed while attempting to access environment variable NO_MATCH
     * @group stable
     */
    public function testSecretsException()
    {
        $this->secrets()->get('NO_MATCH');
    }
}
