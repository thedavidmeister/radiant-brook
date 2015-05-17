<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\API;
use GuzzleHttp\Client;

/**
 * Base class for private Bitstamp API endpoint wrappers.
 */
abstract class PrivateAPI extends API
{
    /**
     * Handles DI.
     * @param Client                  $client
     * @param PrivateAPIAuthenticator $auth
     */
    public function __construct(
        Client $client,
        PrivateAPIAuthenticator $auth
    )
    {
        $this->auth = $auth;
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendRequest()
    {
        $body = array_merge((array) $this->params, $this->auth->getAuthParams());

        return $this->client->post($this->url(), ['body' => $body]);
    }

    /**
     * {@inheritdoc}
     */
    protected function validateParam($key, $value)
    {
        if (in_array($key, ['key', 'signature', 'nonce'])) {
            throw new \Exception('You cannot directly set authentication parameters');
        }
    }
}
