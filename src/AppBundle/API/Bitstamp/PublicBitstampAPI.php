<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;

/**
 * Base class for all Bitstamp public API endpoint wrappers.
 */
abstract class PublicBitstampAPI extends BitstampAPI
{

    protected $data;

    /**
     * Gets data from the public Bitstamp API endpoint.
     *
     * Bitstamp data is provided in JSON format, we decode it and statically
     * cache it for the current request.
     *
     * @return mixed
     *   The decoded data from Bitstamp.
     */
    public function data()
    {
        if (!isset($this->data)) {
            $this->datetime(new \DateTime());
            $this->data = $this->client->get($this->url())->json();
        }

        // @todo - add logging!
        return $this->data;
    }

}
