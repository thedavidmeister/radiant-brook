<?php

namespace AppBundle\Tests\API\Bitstamp;

use AppBundle\Tests\GuzzleTestTrait;
use Respect\Validation\Validator as v;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Standard tests that can be run on all public API classes.
 */
abstract class AbstractAPITest extends WebTestCase
{
    protected $domain = 'https://www.bitstamp.net/api/';
    protected $serviceNamespace = 'bitstamp';

    protected $className;
    protected $sample;
    protected $sample2;

    // These properties must be set on child classes.
    protected $endpoint;

    use GuzzleTestTrait;

    /**
     * Returns an API object from $this->className with Mocks preconfigured.
     *
     * @return object
     */
    protected function getClass($mockType = null)
    {
        v::notEmpty()->string()->check($this->className);

        return new $this->className($this->client($mockType), $this->mockLogger());
    }

    /**
     * Tests that we can clear the parameters previously set.
     *
     * @group stable
     */
    public function testClearParameters()
    {
        $class = $this->getClass();

        $class->setParam('foo', 'bar');
        $class->clearParams();
        $this->assertNull($class->getParam('foo'));
    }

    /**
     * Data provider for testRequiredParameters.
     *
     * @return array
     */
    public function dataRequiredParameters()
    {
        $class = $this->getClass();

        $testMaster = [];
        foreach ($class->requiredParams() as $required) {
            $testMaster[$required] = $required;
        }

        $tests = [];
        foreach ($class->requiredParams() as $required) {
            $test = $testMaster;
            unset($test[$required]);
            $tests[] = [$test, $required];
        }

        // Placeholder sillyness for anything with no required parameters.
        if (empty($class->requiredParams())) {
            $tests[] = [['foo'], 'bar'];
        }

        return $tests;
    }

    /**
     * Test require parameters.
     *
     * @dataProvider dataRequiredParameters
     * @group stable
     *
     * @param array  $params
     *   Parameters array missing required parameters.
     *
     * @param string $required
     *   The required parameter that is missing.
     */
    public function testRequiredParameters($params, $required)
    {
        $class = $this->getClass();
        if ($class->requiredParams() === []) {
            // Nothing to test here.
            $this->assertTrue(true);

            return;
        }

        $this->setExpectedException('Exception', 'Required parameter ' . $required . ' must be set for endpoint');
        $class->clearParams();
        $class->setParams($params);
        $class->execute();
    }

    /**
     * Test basic setters and getters for parameters.
     *
     * @group stable
     */
    public function testParams()
    {
        $class = $this->getClass();
        $testParams = ['foo' => 'bar', 'one' => 'two'];
        $class->setParams($testParams);
        $this->assertSame($testParams, array_intersect($testParams, $class->getParams()));
        $this->assertSame($class->getParam('foo'), 'bar');

        $class->setParam('this', 'that');
        $this->assertSame($class->getParam('this'), 'that');
        $this->assertSame($class->getParams()['this'], 'that');
    }

    /**
     * Tests that we can pickup a Bitstamp "error".
     *
     * @expectedException Exception
     * @expectedExceptionMessage Bitstamp error: {"error":"Bitstamp likes to report errors as 200"}
     * @group stable
     */
    public function testBitstampError()
    {
        $class = $this->getClass('error');
        $class->execute();
    }

    /**
     * Data provider for testResponseErrorHandling.
     *
     * @return integer[][]
     */
    public function badResponseCodes()
    {
        // Everything that is not a 200 is "bad" because we want to be very
        // careful when dealing with remote API weirdness.
        // @see http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        return [
            // 100 Continue.
            [100],
            // 101 Switching Protocols.
            [101],
            // 102 Processing (WebDAV; RFC 2518).
            [102],
            // 201 Created.
            [201],
            // 202 Accepted.
            [202],
            // 203 Non-Authoritative Information (since HTTP/1.1).
            [203],
            // 204 No Content.
            [204],
            // 205 Reset Content.
            [205],
            // 206 Partial Content (RFC 7233).
            [206],
            // 207 Multi-Status (WebDAV; RFC 4918).
            [207],
            // 208 Already Reported (WebDAV; RFC 5842).
            [208],
            //226 IM Used (RFC 3229).
            [226],
            // 300 Multiple Choices.
            [300],
            // 301 Moved Permanently.
            [301],
            // 302 Found.
            [302],
            // 303 See Other (since HTTP/1.1).
            [303],
            // 304 Not Modified (RFC 7232).
            [304],
            // 305 Use Proxy (since HTTP/1.1).
            [305],
            // 306 Switch Proxy.
            [306],
            // 307 Temporary Redirect (since HTTP/1.1).
            [307],
            // 308 Permanent Redirect (RFC 7538).
            [308],
            // 400 Bad Request.
            [400],
            // 401 Unauthorized (RFC 7235).
            [401],
            // 402 Payment Required.
            [402],
            // 403 Forbidden.
            [403],
            // 404 Not Found.
            [404],
            // 405 Method Not Allowed.
            [405],
            // 406 Not Acceptable.
            [406],
            // 407 Proxy Authentication Required (RFC 7235).
            [407],
            // 408 Request Timeout.
            [408],
            // 409 Conflict.
            [409],
            // 410 Gone.
            [410],
            // 411 Length Required.
            [411],
            // 412 Precondition Failed (RFC 7232).
            [412],
            // 413 Request Entity Too Large.
            [413],
            // 414 Request-URI Too Long.
            [414],
            // 415 Unsupported Media Type.
            [415],
            // 416 Requested Range Not Satisfiable (RFC 7233).
            [416],
            // 417 Expectation Failed.
            [417],
            // 418 I'm a teapot (RFC 2324).
            [418],
            // 419 Authentication Timeout (not in RFC 2616).
            [419],
            // 420 Method Failure (Spring Framework).
            // 420 Enhance Your Calm (Twitter).
            [420],
            // 421 Misdirected Request (HTTP/2).
            [421],
            // 422 Unprocessable Entity (WebDAV; RFC 4918).
            [422],
            // 423 Locked (WebDAV; RFC 4918).
            [423],
            // 424 Failed Dependency (WebDAV; RFC 4918).
            [424],
            // 426 Upgrade Required.
            [426],
            // 428 Precondition Required (RFC 6585).
            [428],
            // 429 Too Many Requests (RFC 6585).
            [429],
            // 431 Request Header Fields Too Large (RFC 6585).
            [431],
            // 440 Login Timeout (Microsoft).
            [440],
            // 444 No Response (Nginx).
            [444],
            // 449 Retry With (Microsoft).
            [449],
            // 450 Blocked by Windows Parental Controls (Microsoft).
            [450],
            // 451 Unavailable For Legal Reasons (Internet draft).
            // 451 Redirect (Microsoft).
            [451],
            // 494 Request Header Too Large (Nginx).
            [494],
            // 495 Cert Error (Nginx).
            [495],
            // 496 No Cert (Nginx).
            [496],
            // 497 HTTP to HTTPS (Nginx).
            [497],
            // 498 Token expired/invalid (Esri).
            [498],
            // 499 Client Closed Request (Nginx).
            // 499 Token required (Esri).
            [499],
            // 500 Internal Server Error.
            [500],
            // 501 Not Implemented.
            [501],
            // 502 Bad Gateway.
            [502],
            // 503 Service Unavailable.
            [503],
            // 504 Gateway Timeout.
            [504],
            // 505 HTTP Version Not Supported.
            [505],
            // 506 Variant Also Negotiates (RFC 2295).
            [506],
            // 507 Insufficient Storage (WebDAV; RFC 4918).
            [507],
            // 508 Loop Detected (WebDAV; RFC 5842).
            [508],
            // 509 Bandwidth Limit Exceeded (Apache bw/limited extension).
            [509],
            // 510 Not Extended (RFC 2774).
            [510],
            // 511 Network Authentication Required (RFC 6585).
            [511],
            // 598 Network read timeout error (Unknown).
            [598],
            // 599 Network connect timeout error (Unknown).
            [599],
        ];
    }

    /**
     * Tests that we can spot obvious errors in the API responses.
     *
     * @dataProvider badResponseCodes
     * @group stable
     *
     * @param int $responseCode
     *   The response code to test.
     */
    public function testResponseErrorHandling($responseCode)
    {
        $this->setExpectedExceptionRegExp(
            'Exception',
            '/^Server error response|^Client error response|^Bitstamp response was not a 200/'
        );
        $class = $this->getClass($responseCode);
        $class->execute();
    }

    /**
     * Tests that the class can be built as a service.
     *
     * @group stable
     */
    public function testService()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $kernel->getContainer()->get($this->serviceNamespace . '.' . $this->endpoint);
    }

    /**
     * Test mocks of the execute() method.
     *
     * @group stable
     */
    public function testExecute()
    {
        $class = $this->getClass();

        v::notEmpty()->string()->check($this->sample);
        v::notEmpty()->string()->check($this->sample2);

        // Guzzle uses the json_decode() method of PHP and uses arrays rather than
        // stdClass objects for objects.
        $expected = $this->objectToArrayRecursive(json_decode($this->sample));
        $this->assertSame($expected, $class->execute());

        // execute() has no internal cache, unlike data(). We should see fresh
        // samples every time.
        $expected2 = $this->objectToArrayRecursive(json_decode($this->sample2));
        $this->assertSame($expected2, $class->execute());
    }


    /**
     * Test mocks of the data() method.
     *
     * @group stable
     */
    public function testData()
    {
        $class = $this->getClass();

        v::notEmpty()->string()->check($this->sample);

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
     *
     * @group stable
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
     * @group stable
     */
    public function testDatesData()
    {
        // Get a DateTime for now.
        $class = $this->getClass();

        // datetime() should be null at first.
        $this->assertSame($class->datetime(), null);

        // Trigger an API call that should update the internal DateTime.
        $class->data();
        $now = new \DateTime();
        $this->assertSame($class->datetime()->format('U'), $now->format('U'));
    }
}
