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
    public function mockAuthenticator()
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

        $lastRequest = $class->client->history->getLastRequest();

        // All the authentication params should match our mock.
        $this->assertSame($lastRequest->getBody()->getField('key'), 'foo');
        $this->assertSame($lastRequest->getBody()->getField('nonce'), 'bar');
        $this->assertSame($lastRequest->getBody()->getField('signature'), 'baz');
    }

    /**
     * Data provider for testAuthenticationParamsExceptions().
     *
     * @return array
     */
    public function dataAuthenticationParamsExceptions() {
        return [
          ['key', 'foo'],
          ['signature', 'foo'],
          ['nonce', 'foo'],
        ];
    }

    /**
     * Test that parameters reserved for authentication throw exceptions.
     *
     * @dataProvider dataAuthenticationParamsExceptions
     *
     * @expectedException Exception
     * @expectedExceptionMessage You cannot directly set authentication parameters
     *
     * @param  string $key
     *   The name of the parameter to test.
     *
     * @param  mixed $value
     *   The value of the parameter to test.
     */
    public function testAuthenticationParamsExceptions($key, $value) {
        $class = $this->getClass();

        $class->setParam($key, $value);
    }

    /**
     * Returns an API object from $this->className with Mocks preconfigured.
     *
     * PrivateAPI needs to mock an authenticator as well as a client.
     *
     * @return mixed
     */
    protected function getClass($mockType = self::DEFAULT_MOCK_TYPE)
    {
        $class = new $this->className($this->client($mockType), $this->mockLogger(), $this->mockAuthenticator());
        if (isset($this->requiredParamsFixture)) {
            $class->setParams($this->requiredParamsFixture);
        }

        return $class;
    }
}
