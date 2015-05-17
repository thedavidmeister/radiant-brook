<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\APIInterface;
use GuzzleHttp\Client;

/**
 * Implements APIInterface for Bitstamp.
 */
abstract class API implements APIInterface
{
    /**
     * The domain of the Bitstamp API.
     */
    const DOMAIN = 'https://www.bitstamp.net/api/';

    /**
     * Placeholder for Bitstamp API endpoints.
     *
     * Override this in the child class when actually implementing an endpoint.
     */
    const ENDPOINT = '***';

    // The client is public for ease of Unit Testing ONLY.
    // Bad Things will happen if you mess with it directly in production code.
    public $client;

    // The DateTime of the last API call.
    protected $datetime;

    // Storage for data().
    protected $data;

    // Parameters storage.
    protected $params = [];

    // If set to true, the full JSON response will be logged during execute().
    protected $logFullResponse = true;

    /**
     * Constructor.
     *
     * @param Client $client
     *   A Guzzle compatible HTTP client.
     *
     * @param Logger $logger
     *   A PSR3 compatible Logger.
     */
    public function __construct(Client $client, \Psr\Log\LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Returns an array of required parameter keys that must be set.
     *
     * $this->execute() will always fail until the required parameters have been
     * set via $this->setParam().
     *
     * @return array
     *   An array of parameter keys that must be set.
     */
    public function requiredParams()
    {
        return [];
    }

    /**
     * Set multiple parameters from an array.
     *
     * @param array $array
     *   An associative array of parameters to set.
     *
     * @return PrivateAPI
     *   Returns the PrivateAPI object to facilitate method chaining.
     */
    public function setParams(array $array)
    {
        foreach ((array) $array as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * Sets a parameter in simple key/value format.
     *
     * @see $this->validateParam()
     *
     * @param string $key
     *   The parameter to set.
     *
     * @param mixed  $value
     *   The value to set for this parameter.
     *
     * @return PrivateBitstampAPI
     *   $this
     */
    public function setParam($key, $value)
    {
        $this->validateParam($key, $value);
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Throws exceptions for parameters that fail validation.
     *
     * @see $this->setParam().
     *
     * @param  string $key
     *   The parameter to validate.
     *
     * @param  mixed $value
     *   The value of the parameter being validated.
     *
     * @return null
     *   Don't return anything, just throw exceptions for anything that fails.
     */
    protected function validateParam($key, $value)
    {
        // Throw exceptions for invalid parameters in child implementations.
    }

    /**
     * Send a request to the endpoint and return the Guzzle HTTPClient Response.
     */
    protected function sendRequest()
    {
        // Return a Guzzle Response object from something like $client->post()
        // or $client->get().
    }

    /**
     * Returns previously set parameters.
     *
     * @return array
     *   The previously set parameters.
     */
    public function getParams()
    {
        return $this->params;
    }

    protected function ensureRequiredParams()
    {
        foreach ($this->requiredParams() as $required) {
            if (!isset($this->params[$required])) {
                throw new \Exception('Required parameter ' . $required . ' must be sent for endpoint ' . $this->endpoint());
            }
        }
    }

    /**
     * Validates, executes and logs the remote API call.
     *
     * @return array
     *   A PHP array of data, as JSON decoded by Guzzle.
     */
    public function execute()
    {
        $this->ensureRequiredParams();

        $response = $this->sendRequest();

        if ($response->getStatusCode() !== 200) {
            $e = new \Exception('Bitstamp response was not a 200');
            $this->logger->error('Bitstamp response was not a 200', ['response' => $response->getStatusCode()]);
            throw $e;
        }

        $data = $response->json();

        if (!empty($data['error'])) {
            $e = new \Exception('Bitstamp error: ' . $data['error']);
            $this->logger->error('Bitstamp error', ['data' => $data, 'exception' => $e]);
            throw $e;
        }

        // Logging all response data is impractical for some endpoints, such as
        // full OrderBook info, which is huge, so we expose a way to disable
        // this behaviour.
        $logData = $this->logFullResponse ? $data : '-- This endpoint does not have full response logging enabled --';
        $this->logger->info('Response from ' . $this->endpoint(), ['data' => $logData]);

        $this->datetime(new \DateTime());

        return $data;
    }

    /**
     * Thin wrapper around execute() to mirror public Bitstamp API class.
     *
     * Usage of this function does not make sense for all private API endpoints.
     * Use execute() directly if attempting to achieve something with a side
     * effect as data() ONLY calls execute() if it has NOT already been called
     * by data().
     *
     * i.e. data() caches the result of the last call to data() and stops
     * using execute().
     *
     * This is good for performance and avoiding spamming Bitstamp for private
     * endpoints that are read-only.
     *
     * This is terrible for any endpoint that needs to write to Bitstamp.
     *
     * @return array
     *   Data from execution cast to an array.
     */
    public function data()
    {
        if (!isset($this->data)) {
            $this->data = $this->execute();
        }

        return (array) $this->data;
    }

    /**
     * Returns the DateTime of the most recent execution.
     *
     * @param DateTime $new
     *   The new DateTime to lodge as last updated time.
     *
     * @return DateTime
     */
    public function datetime($new = null)
    {
        if (isset($new)) {
            if (!($new instanceof \DateTime)) {
                throw new \Exception('New datetime must be a DateTime object.');
            }
            $this->datetime = $new;
        }

        return $this->datetime;
    }

    /**
     * {@inheritDoc}
     */
    public function domain()
    {
        return $this::DOMAIN;
    }

    /**
     * {@inheritDoc}
     */
    public function endpoint()
    {
        if ($this::ENDPOINT === '***') {
            throw new \Exception('The Bitstamp API endpoint has not been set for this class.');
        }

        return $this::ENDPOINT;
    }

    /**
     * {@inheritDoc}
     */
    public function url()
    {
        // The Bitstamp URL must end with a trailing '/' or it will throw a security
        // exception.
        return rtrim($this->domain() . $this->endpoint(), '/') . '/';
    }

}
