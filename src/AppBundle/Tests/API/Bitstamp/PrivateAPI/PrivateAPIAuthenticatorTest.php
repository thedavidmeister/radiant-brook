<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPIAuthenticator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests the Bitstamp Private API authenticator class.
 */
class PrivateAPIAuthenticatorTest extends WebTestCase
{
    /**
     * @return \AppBundle\Secrets
     */
    protected function mockSecrets()
    {
        $secrets = $this
        ->getMockBuilder('AppBundle\Secrets')
        ->getMock();

        $secrets->method('get')
        ->will($this->returnValueMap([
            ['BITSTAMP_CLIENT_ID', 'foo'],
            ['BITSTAMP_KEY', 'bar'],
            ['BITSTAMP_SECRET', 'bing'],
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
     *
     * @group stable
     */
    public function testAuthParameters()
    {
        $authenticator = $this->authenticator();
        $secrets = $this->mockSecrets();

        $authParams = $authenticator->getAuthParams();

        $laterAuthParams = $authenticator->getAuthParams();
        $this->assertGreaterThan($authParams['nonce'], $laterAuthParams['nonce']);

        // Check some basic things about the nonce.
        $this->assertTrue((int) $authParams['nonce'] == $authParams['nonce']);
        $this->assertGreaterThan(0, $authParams['nonce']);

        $this->assertSame($authParams['key'], $secrets->get('BITSTAMP_KEY'));
        $this->assertSame($authParams['signature'], $this->signature($authParams['nonce'], $secrets->get('BITSTAMP_CLIENT_ID'), $secrets->get('BITSTAMP_KEY'), $secrets->get('BITSTAMP_SECRET')));
    }
}
