<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\API;
use GuzzleHttp\Client;

/**
 * Base class for private Bitstamp API endpoint wrappers.
 */
abstract class PrivateAPI extends API
{

    // Parameters storage.
    protected $params;

    // Storage for data().
    protected $_data;

    public function __construct(
        Client $client,
        PrivateAPIAuthenticator $auth
    ) 
    {
        $this->auth = $auth;
        parent::__construct($client);
    }

    /**
     * Execute the authenticated, private API call.
     *
     * This uses the parameters previously set with $this->setParams() to hit
     * Bitstamp. Authentication will be handled automatically and the DateTime
     * of the API call will be recorded and can be accessed from
     * $this->datetime().
     *
     * @see requiredParams()
     * @see setParams()
     * @see datetime()
     *
     * @return mixed
     *   The JSON decoded reponsed from Bitstamp.
     */
    public function execute()
    {
        foreach ($this->requiredParams() as $required) {
            if (!isset($this->params[$required])) {
                throw new \Exception('Required parameter ' . $required . ' must be sent for endpoint ' . $this->endpoint());
            }
        }

        $this->datetime(new \DateTime());

        $body = array_merge((array) $this->params, $this->auth->getAuthParams());

        $response = $this->client->post($this->url(), ['body' => $body]);

        $data = $response->json();

        // @todo - add logging!
        if (!empty($data['error'])) {
            throw new \Exception('Bitstamp error: ' . $data['error']);
        }

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
        if (!isset($this->_data)) {
            $this->_data = $this->execute();
        }

        return (array) $this->_data;
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

    public function setParams($array) 
    {
        foreach ((array) $array as $key => $value) {
            $this->setParam($key, $value);
        }

        return $this;
    }

    /**
     * Sets a parameter in simple key/value format.
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
        if (in_array($key, ['key', 'signature', 'nonce'])) {
            throw new \Exception('You cannot directly set authentication parameters');
        }

        $this->params[$key] = $value;

        return $this;
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
}
