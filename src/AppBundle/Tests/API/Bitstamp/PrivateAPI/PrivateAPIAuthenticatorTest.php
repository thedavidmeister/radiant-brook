<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPIAuthenticator;

/**
 * Tests the Bitstamp Private API authenticator class.
 */
class PrivateAPIAuthenticatorTest extends WebTestCase
{
    protected function mockSecrets()
    {
        $secrets = $this
        ->getMockBuilder('AppBundle\Secrets')
        ->getMock();

        $secrets->method('get')
        ->will($this->returnValueMap([
            ['client_id', 'foo'],
            ['key', 'bar'],
            ['secret', 'bing'],
        ]));

        return $secrets;
    }

    protected function authenticator()
    {
        return new PrivateAPIAuthenticator($this->mockSecrets());
    }

    /**
     * Procedural version of the signature generation to check against.
     * @param  int $nonce
     * @param  string $id
     * @param  string $key
     * @param  string $secret
     * @return string
     */
    protected function signature($nonce, $id, $key, $secret)
    {
        $data = $nonce . $id . $key;

        return strtoupper(hash_hmac('sha256', $data, $secret));
    }

    /**
     * Tests the parameters used for authentication with Bitstamp.
     */
    public function testAuthParameters()
    {
        $authenticator = $this->authenticator();
        $secrets = $this->mockSecrets();

        $authParams = $authenticator->getAuthParams();

        $laterAuthParams = $authenticator->getAuthParams();
        $this->assertTrue($laterAuthParams['nonce'] > $authParams['nonce']);

        // Check some basic things about the nonce.
        $this->assertTrue(is_int($authParams['nonce']));
        $this->assertTrue($authParams['nonce'] > 0);

        $this->assertSame($authParams['key'], $secrets->get('key'));
        $this->assertSame($authParams['signature'], $this->signature($authParams['nonce'], $secrets->get('client_id'), $secrets->get('key'), $secrets->get('secret')));
    }
}
