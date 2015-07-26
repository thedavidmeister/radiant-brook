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
     * Tests that secrets can find variables in the environment.
     *
     * @group stable
     */
    public function testSecretsEnv()
    {
        putenv('FOOBAR=BINGBAZ');
        $this->assertSame('BINGBAZ', $this->secrets()->get('FOOBAR'));
    }

    /**
     * Tests exceptions thrown when secrets are not found.
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
