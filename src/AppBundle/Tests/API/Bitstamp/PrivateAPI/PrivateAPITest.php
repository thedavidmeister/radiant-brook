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
}
