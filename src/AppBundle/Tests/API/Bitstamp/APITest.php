<?php

namespace AppBundle\Tests\API\Bitstamp;

use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Mock;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Standard tests that can be run on all public API classes.
 */
abstract class APITest extends WebTestCase
{
    protected $endpoint;
    protected $domain = 'https://www.bitstamp.net/api/';
    protected $sample;
    protected $sample2;

    protected $history;

    /**
     * Returns an API object from $this->className with Mocks preconfigured.
     *
     * @return mixed
     */
    protected function getClass()
    {
          return new $this->className($this->client());
    }

    /**
     * Convert the samples into a Guzzle Mock.
     *
     * @return Mock
     */
    protected function mock()
    {
        return new Mock([
            new Response(200, [], Stream::factory($this->sample)),
            new Response(200, [], Stream::factory($this->sample2)),
        ]);
    }

    protected function client()
    {
        $client = new Client();
        $this->history = new History();

        // Add the mock subscriber to the client.
        $client->getEmitter()->attach($this->mock());
        $client->getEmitter()->attach($this->history);

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

    /**
     * Tests that the class can be built as a service.
     */
    public function testService()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $kernel->getContainer()->get($this->servicename);
    }

    /**
     * Test mocks of the data() method.
     */
    public function testData()
    {
        $class = $this->getClass();

        // Guzzle uses the json_decode() method of PHP and uses arrays rather than
        // stdClass objects for objects.
        $expected = $this->objectToArrayRecursive(json_decode($this->sample));

        $this->assertSame($expected, $class->data());

        // data() should internally cache for the current request. We should not
        // see sample2.
        $this->assertSame($expected, $class->data());
    }

    /**
     * Test that the endpoint URLs are correct.
     */
    public function testEndpoints()
    {
        $class = $this->getClass();

        $this->assertSame($this->domain, $class->domain());
        $this->assertSame($this->endpoint, $class->endpoint());
        $this->assertSame($this->domain . $this->endpoint . '/', $class->url());
    }

    /**
     * Test that timestamp dates are recorded properly.
     *
     * @group slow
     */
    public function testDatesData()
    {
        // Get a DateTime for now.
        $class = $this->getClass();

        // datetime() should be null at first.
        $this->assertSame($class->datetime(), null);

        // Trigger an API call that should update the internal DateTime.
        $now = new \DateTime();
        $class->data();
        $this->assertSame($class->datetime()->format('U'), $now->format('U'));

        // Even after a second, and a new data call, datetime should not change as
        // data() should be cached.
        sleep(1);
        $class->data();
        $this->assertSame($class->datetime()->format('U'), $now->format('U'));
    }
}
