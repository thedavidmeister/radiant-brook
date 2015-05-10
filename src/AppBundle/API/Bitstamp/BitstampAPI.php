<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\APIInterface;
use GuzzleHttp\Client;

abstract class BitstampAPI implements APIInterface
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

    /**
   * Constructor.
   *
   * Creates a Guzzle client needed to interact with the remote API endpoint.
   */
    public function __construct()
    {
        $this->client = new Client(['base_url' => $this->domain()]);
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
