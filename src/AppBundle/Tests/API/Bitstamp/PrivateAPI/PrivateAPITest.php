<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\API\Bitstamp\APITest;
use AppBundle\Secrets;

abstract class PrivateAPITest extends APITest
{
  public function testAuthenticationParams()
  {
    $class = $this->getClass();

    // Trigger an execute to test what is being sent off.
    $class->execute();

    $lastRequest = $this->history->getLastRequest();

    // Key should match our secrets.
    $secrets = new Secrets();

    $this->assertSame($lastRequest->getBody()->getField('key'), $secrets->get('key'));

    // Check the nonce is numeric and store it so we can compare it later.
    $firstNonce = $lastRequest->getBody()->getField('nonce');
    $this->assertTrue(is_numeric($firstNonce));
    $this->assertTrue($firstNonce > 0);

    // Check we have a signature.
    $this->assertTrue(!empty($lastRequest->getBody()->getField('signature')));
  }
}
