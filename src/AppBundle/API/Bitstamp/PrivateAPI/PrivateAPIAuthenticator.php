<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

class PrivateAPIAuthenticator
{

    // Last used nonce storage.
    protected $_nonce;

    // POST parameter storage.
    protected $params;

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

    public function __construct(\AppBundle\Secrets $secrets) {
      $this->secrets = $secrets;
    }

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
        $this->params[$this::KEY] = $this->secrets->get($this::KEY);

        return $this;
    }

    /**
     * Ensures the HMAC signature is set as per Bitstamp API docs.
     */
    protected function ensureSignature()
    {
        $data = $this->nonce() . $this->secrets->get($this::CLIENT_ID) . $this->secrets->get($this::KEY);
        $this->params[$this::SIGNATURE] = strtoupper(hash_hmac('sha256', $data, $this->secrets->get($this::SECRET)));

        return $this;
    }

    /**
     * Handles required authentication parameters for Bitstamp API security.
     */
    public function getAuthParams()
    {
        $this
        ->ensureKey()
        ->ensureNonce()
        ->ensureSignature();

        return $this->params;
    }
}
