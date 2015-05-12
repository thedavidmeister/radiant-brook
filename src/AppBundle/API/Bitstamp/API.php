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

    protected $client;

    // The DateTime of the last API call.
    protected $datetime;

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
     * Constructor.
     *
     * @param Client $client
     *   A Guzzle compatible HTTP client.
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
