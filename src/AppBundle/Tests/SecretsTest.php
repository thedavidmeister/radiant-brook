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
     * Tests that secrets can find variables set by putenv().
     *
     * @covers Secrets::set
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
            $tuple = [uniqid(), uniqid()];
            $setFunc($tuple);
            $this->assertSame($tuple[1], $this->secrets()->get($tuple[0]));
        });
    }

    /**
     * Tests exceptions thrown when secrets are not found.
     *
     * @covers Secrets::get
     *
     * @expectedException Exception
     * @expectedExceptionMessage Environment variable not found: no match - This probably means you did not set your .env file up properly, you dingus.
     * @group stable
     */
    public function testSecretsException()
    {
        $this->secrets()->get('no match');
    }
}
