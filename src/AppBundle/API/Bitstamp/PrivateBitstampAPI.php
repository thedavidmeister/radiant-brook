<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;
use Symfony\Component\Finder\Finder;

/**
 * Base class for private Bitstamp API endpoint wrappers.
 */
abstract class PrivateBitstampAPI extends BitstampAPI
{

    // Last used nonce storage.
    protected $_nonce;

    // Secrets storage.
    protected $keysArray;

    // Parameters storage.
    protected $params;

    // Storage for data().
    protected $_data;

    // Nonce parameter name.
    const NONCE = 'nonce';

    // API key parameter name.
    const KEY = 'key';

    // HMAC signature parameter name.
    const SIGNATURE = 'signature';

    // Client ID parameter name.
    const CLIENT_ID = 'client_id';

    // API key secret parameter name.
    const SECRET = 'secret';

    /**
     * Returns a Nonce required by Bitstamp.
     *
     * Nonce is a regular integer number. It must be increasing with every request
     * you make. Read more about it here. Example: if you set nonce to 1 in your
     * first request, you must set it to at least 2 in your second request. You
     * are not required to start with 1. A common practice is to use unix time for
     * that parameter.
     *
     * @see http://en.wikipedia.org/wiki/Cryptographic_nonce
     */
    protected function nonce()
    {
        // @todo - test this.
        if (!isset($this->_nonce)) {
            // Generate a nonce as microtime, with as-string handling to avoid problems
            // with 32bits systems.
            $mt = explode(' ', microtime());
            $this->_nonce = $mt[1] . substr($mt[0], 2, 6);
        }
        // @todo - sleep for one microsecond to ensure we never repeat a nonce.
        return $this->_nonce;
    }

    /**
     * Ensures the cryptographic nonce is set as per Bitstamp API docs.
     */
    protected function ensureNonce()
    {
        $this->params[$this::NONCE] = $this->nonce();

        return $this;
    }

    /**
     * Ensures the secret API key is set as per Bitstamp API docs.
     */
    protected function ensureKey()
    {
        $this->params[$this::KEY] = $this->secrets()[$this::KEY];

        return $this;
    }

    /**
     * Ensures the HMAC signature is set as per Bitstamp API docs.
     */
    protected function ensureSignature()
    {
        $data = $this->nonce() . $this->secrets()[$this::CLIENT_ID] . $this->secrets()[$this::KEY];
        $this->params[$this::SIGNATURE] = strtoupper(hash_hmac('sha256', $data, $this->secrets()[$this::SECRET]));

        return $this;
    }

    /**
     * Extracts secret keys from the file system or environment variables.
     */
    protected function secrets()
    {
        if (!isset($this->keysArray)) {
            $keynames = [$this::CLIENT_ID, $this::KEY, $this::SECRET];

            // First try environment variables.
            foreach ($keynames as $keyname) {
                if (getenv($keyname)) {
                    $this->keysArray[$keyname] = trim(getenv($keyname));
                }
            }

            // Try file based API key storage.
            if (empty($this->keysArray)) {
                $finder = new Finder();
                // @todo - make this less hacky.
                $keys = $finder->in(__DIR__ . '/../../Resources/keys')->files();
                foreach ($keys as $key) {
                    $this->keysArray[$key->getFilename()] = trim(file_get_contents($key->getRealpath()));
                }
            }

        }

        return $this->keysArray;
    }

    /**
     * Handles required authentication parameters for Bitstamp API security.
     */
    protected function authenticate()
    {
        $this
        ->ensureKey()
        ->ensureNonce()
        ->ensureSignature();
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

        $this->authenticate();
        $this->datetime(new \DateTime());

        // @todo - add logging!
        $result = $this->client->post($this->url(), ['body' => $this->params])->json();

        return $result;
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
