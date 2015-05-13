<?php

namespace AppBundle\Tests\API\Bitstamp\PublicAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\API\Bitstamp\APITest;

/**
 * Standard tests that can be run on all public API classes.
 */
abstract class PublicAPITest extends APITest
{

}
