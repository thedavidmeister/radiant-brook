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
   * Signature is a HMAC-SHA256 encoded message containing: nonce, client ID and
   * API key. The HMAC-SHA256 code must be generated using a secret key that was
   * generated with your API key. This code must be converted to it's
   * hexadecimal representation (64 uppercase characters).
   *
   * @see https://www.bitstamp.net/api/
   *
   * @param  int $nonce  [description]
   * @param  [type] $id     [description]
   * @param  [type] $key    [description]
   * @param  [type] $secret [description]
   * @return [type]         [description]
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
