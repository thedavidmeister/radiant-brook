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

abstract class PrivateAPITest extends APITest
{
  public function getMockAuthenticator() {
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
     * Test mocks of the data() method.
     */
    public function testExecute()
    {
        $class = $this->getClass();

        // Guzzle uses the json_decode() method of PHP and uses arrays rather than
        // stdClass objects for objects.
        $expected = $this->objectToArrayRecursive(json_decode($this->sample));
        $this->assertSame($expected, $class->execute());

        // execute() has no internal cache, unlike data(). We should see fresh
        // samples every time.
        $expected2 = $this->objectToArrayRecursive(json_decode($this->sample2));
        $this->assertSame($expected2, $class->execute());
    }
}
