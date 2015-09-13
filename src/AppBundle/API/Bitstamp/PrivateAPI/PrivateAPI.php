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
     *
     * @param Client                  $client
     *   A Guzzle HTTP compatible client.
     *
     * @param \Psr\Log\LoggerInterface                  $logger
     *   A PSR3 compatible Logger.
     *
     * @param PrivateAPIAuthenticator $auth
     */
    public function __construct(
        Client $client,
        \Psr\Log\LoggerInterface $logger,
        PrivateAPIAuthenticator $auth
    )
    {
        parent::__construct($client, $logger);
        $this->auth = $auth;
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
