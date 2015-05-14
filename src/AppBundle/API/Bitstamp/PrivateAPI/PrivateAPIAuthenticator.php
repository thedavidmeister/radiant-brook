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

    public function __construct(\AppBundle\Secrets $secrets) 
    {
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
     * We generate the nonce separate to getting it because it must be exactly
     * the same per request in 'nonce' and embedded in 'signature' BUT must
     * always be different between requests. This is easier to handle with two
     * dedicated methods.
     *
     * @see http://en.wikipedia.org/wiki/Cryptographic_nonce
     */
    protected function generateNonce() 
    {
        // Generate a nonce as microtime, with as-string handling to avoid problems
        // with 32bits systems.
        $mt = explode(' ', microtime());
        $candidate = $mt[1] . substr($mt[0], 2, 6);

        if ($candidate <= $this->_nonce) {
            $this->_nonce++;
        } else {
            $this->_nonce = $candidate;
        }
    }

    protected function nonce() 
    {
        return $this->_nonce;
    }

    /**
     * Ensures the secret API key is set as per Bitstamp API docs.
     */
    protected function key()
    {
        return $this->secrets->get($this::KEY);
    }

    /**
     * Ensures the HMAC signature is set as per Bitstamp API docs.
     */
    protected function ensureSignature()
    {
        $data = $this->nonce() . $this->secrets->get($this::CLIENT_ID) . $this->secrets->get($this::KEY);
        return strtoupper(hash_hmac('sha256', $data, $this->secrets->get($this::SECRET)));
    }

    /**
     * Handles required authentication parameters for Bitstamp API security.
     */
    public function getAuthParams()
    {
        // Generate a new nonce for this set of params.
        $this->generateNonce();

        return [
            $this::NONCE => $this->nonce(),
            $this::KEY => $this->key(),
            $this::SIGNATURE => $this->ensureSignature(),
        ];
    }
}
