<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\API\Bitstamp\APITest;
use AppBundle\Secrets;
use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPIAuthenticator;

/**
 * Abstract class for testing bitstamp private API wrappers.
 */
abstract class PrivateAPITest extends APITest
{
    /**
     * Creates a mock authenticator for private API tests.
     *
     * @return PrivateAPIAuthenticator
     */
    public function getMockAuthenticator()
    {
        $authenticator = $this
        ->getMockBuilder('\AppBundle\API\Bitstamp\PrivateAPI\PrivateAPIAuthenticator')
        ->disableOriginalConstructor()
        ->getMock();

        $authenticator->method('getAuthParams')->willReturn([
            'key' => 'foo',
            'nonce' => 'bar',
            'signature' => 'baz',
        ]);

        return $authenticator;
    }

    /**
     * Tests that Bitstamp private API executions include auth parameters.
     */
    public function testAuthenticationParams()
    {
        $class = $this->getClass();

        // Trigger an execute to test what is being sent off.
        $class->execute();

        $lastRequest = $this->history->getLastRequest();

        // All the authentication params should match our mock.
        $this->assertSame($lastRequest->getBody()->getField('key'), 'foo');
        $this->assertSame($lastRequest->getBody()->getField('nonce'), 'bar');
        $this->assertSame($lastRequest->getBody()->getField('signature'), 'baz');
    }

    /**
     * Returns an API object from $this->className with Mocks preconfigured.
     *
     * PrivateAPI needs to mock an authenticator as well as a client.
     *
     * @return mixed
     */
    protected function getClass()
    {
        $class = new $this->className($this->client(), $this->getMockAuthenticator());
        if (isset($this->requiredParamsFixture)) {
            $class->setParams($this->requiredParamsFixture);
        }

        return $class;
    }
}
