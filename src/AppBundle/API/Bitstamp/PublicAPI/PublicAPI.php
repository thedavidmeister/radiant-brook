<?php

namespace AppBundle\API\Bitstamp\PublicAPI;

use AppBundle\API\Bitstamp\API;

/**
 * Base class for all Bitstamp public API endpoint wrappers.
 */
abstract class PublicAPI extends API
{
    /**
     * {@inheritdoc}
     */
    public function sendRequest()
    {
        return $this->client->get($this->url(), ['query' => $this->getParams()]);
    }
}
