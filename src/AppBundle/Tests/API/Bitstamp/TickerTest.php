<?php

namespace AppBundle\Tests\API\Bitstamp;

use AppBundle\API\Bitstamp\Ticker;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class TickerTest extends \PHPUnit_Framework_TestCase
{
    protected $sample = '{"high": "242.90", "last": "240.83", "timestamp": "1431351913", "bid": "240.55", "vwap": "239.57", "volume": "6435.83679504", "low": "237.99", "ask": "240.83"}';
    protected $sample2 = '{"high": "242.60", "last": "241.85", "timestamp": "1431353704", "bid": "240.97", "vwap": "239.59", "volume": "6517.73015869", "low": "237.99", "ask": "241.25"}';

    protected function mock() {
      return new Mock([
        new Response(200, [], Stream::factory($this->sample)),
        new Response(200, [], Stream::factory($this->sample2)),
      ]);
    }

    public function testData() {
      $client = new Client();

      // Add the mock subscriber to the client.
      $client->getEmitter()->attach($this->mock());

      $api = new Ticker($client);

      // Guzzle uses the json_decode() method of PHP and uses arrays rather than
      // stdClass objects for objects.
      $expected = (array) json_decode($this->sample);

      $this->assertSame($expected, $api->data());

      // data() should internally cache for the current request. We should not
      // see sample2.
      $this->assertSame($expected, $api->data());
    }
}
