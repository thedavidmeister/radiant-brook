<?php

namespace AppBundle\Tests;

use AppBundle\API\Bitstamp\PrivateAPI\PrivateAPIAuthenticator;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

trait GuzzleTestTrait
{
    // Traits cannot have constants.
    protected static $defaultMockType = 200;

    /**
     * @param string $className
     *
     * @see Symfony\Bundle\FrameworkBundle\Test\WebTestCase
     *
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    abstract public function getMockBuilder($className);

    abstract protected function sample();

    abstract protected function sample2();

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
     * Convert the samples into a Guzzle Mock.
     *
     * @return Mock
     */
    protected function mock($type = null)
    {
        if (!isset($type)) {
            $type = self::$defaultMockType;
        }

        switch ($type) {
            case 200:
                return new Mock([
                    new Response(200, [], Stream::factory($this->sample())),
                    new Response(200, [], Stream::factory($this->sample2())),
                ]);

            case 'error':
                return new Mock([
                    new Response(200, [], Stream::factory('{"error":"Bitstamp likes to report errors as 200"}')),
                ]);

            // The default behaviour can just be setting the response status
            // code to whatever the "type" is.
            default:
                return new Mock([new Response($type)]);

        }
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function mockLogger()
    {
        $logger = $this
            ->getMockBuilder('\Psr\Log\LoggerInterface')
            ->getMock();

        return $logger;
    }

    protected function client($mockType = null)
    {
        $client = new Client();
        $client->history = new History();

        // Add the mock subscriber to the client.
        $client->getEmitter()->attach($this->mock($mockType));
        $client->getEmitter()->attach($client->history);

        return $client;
    }

    /**
     * @return array
     */
    protected function objectToArrayRecursive($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = $this->objectToArrayRecursive($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }
}
