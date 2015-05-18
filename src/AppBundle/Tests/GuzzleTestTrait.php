<?php

namespace AppBundle\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

trait GuzzleTestTrait
{
    // Traits cannot have constants.
    protected static $defaultMockType = 200;

    protected $sample = 'foo';
    protected $sample2 = 'bar';

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
                    new Response(200, [], Stream::factory($this->sample)),
                    new Response(200, [], Stream::factory($this->sample2)),
                ]);
                break;

            case 'error':
                return new Mock([
                    new Response(200, [], Stream::factory('{"error":"Bitstamp likes to report errors as 200"}')),
                ]);
              break;

            // The default behaviour can just be setting the response status
            // code to whatever the "type" is.
            default:
                return new Mock([new Response($type)]);
                break;

        }
    }

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
