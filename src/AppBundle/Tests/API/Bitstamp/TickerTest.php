<?php

namespace AppBundle\Tests\API\Bitstamp\PublicAPI;

use AppBundle\API\Bitstamp\PublicAPI\Ticker;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;

class TickerTest extends \PHPUnit_Framework_TestCase
{
    protected $endpoint = 'ticker';
    protected $domain = 'https://www.bitstamp.net/api/';
    protected $sample = '{"high": "242.90", "last": "240.83", "timestamp": "1431351913", "bid": "240.55", "vwap": "239.57", "volume": "6435.83679504", "low": "237.99", "ask": "240.83"}';
    protected $sample2 = '{"high": "242.60", "last": "241.85", "timestamp": "1431353704", "bid": "240.97", "vwap": "239.59", "volume": "6517.73015869", "low": "237.99", "ask": "241.25"}';

    /**
     * Convert the samples into a Guzzle Mock.
     *
     * @return Mock
     */
    protected function mock() {
      return new Mock([
        new Response(200, [], Stream::factory($this->sample)),
        new Response(200, [], Stream::factory($this->sample2)),
      ]);
    }

    protected function client() {
      $client = new Client();

      // Add the mock subscriber to the client.
      $client->getEmitter()->attach($this->mock());

      return $client;
    }

    /**
     * Returns a Ticker object with Mocks preconfigured.
     *
     * @return Ticker
     */
    protected function ticker() {
      return new Ticker($this->client(), new \DateTime());
    }

    public function testData() {
      $ticker = $this->ticker();

      // Guzzle uses the json_decode() method of PHP and uses arrays rather than
      // stdClass objects for objects.
      $expected = (array) json_decode($this->sample);

      $this->assertSame($expected, $ticker->data());

      // data() should internally cache for the current request. We should not
      // see sample2.
      $this->assertSame($expected, $ticker->data());
    }

    public function testEndpoints() {
      $ticker = $this->ticker();

      $this->assertSame($this->domain, $ticker->domain());
      $this->assertSame($this->endpoint, $ticker->endpoint());
      $this->assertSame($this->domain . $this->endpoint . '/', $ticker->url());
    }

    public function testDates() {
      // Get a DateTime for now.
      $ticker = $this->ticker();

      // datetime() should be null at first.
      $this->assertSame($ticker->datetime(), null);

      // Trigger an API call that should update the internal DateTime.
      $now = new \DateTime();
      $ticker->data();
      $this->assertSame($ticker->datetime()->format('U'), $now->format('U'));

      // Even after a second, and a new data call, datetime should not change as
      // data() should be cached.
      sleep(1);
      $ticker->data();
      $this->assertSame($ticker->datetime()->format('U'), $now->format('U'));
    }
}
