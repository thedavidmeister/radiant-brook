<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use AppBundle\Tests\API\Bitstamp\AbstractAPITest;

/**
 * Abstract class for testing bitstamp private API wrappers.
 */
abstract class AbstractPrivateAPITest extends AbstractAPITest
{
    protected $requiredParamsFixture;

    /**
     * Tests that Bitstamp private API executions include auth parameters.
     *
     * @group stable
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
    public function dataAuthenticationParamsExceptions()
    {
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
     * @group stable
     *
     * @param  string $key
     *   The name of the parameter to test.
     *
     * @param  mixed  $value
     *   The value of the parameter to test.
     */
    public function testAuthenticationParamsExceptions($key, $value)
    {
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
    protected function getClass($mockType = null)
    {
        $class = new $this->className($this->client($mockType), $this->mockLogger(), $this->mockAuthenticator());
        if (isset($this->requiredParamsFixture)) {
            $class->setParams($this->requiredParamsFixture);
        }

        return $class;
    }
}
