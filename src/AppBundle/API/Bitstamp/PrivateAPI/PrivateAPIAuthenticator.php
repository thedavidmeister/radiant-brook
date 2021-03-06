<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Service to provide POST parameters to authenticate with the Bitstamp API.
 */
class PrivateAPIAuthenticator
{

    // Last used nonce storage.
    protected $nonce;

    // POST parameter storage.
    protected $params;

    // Secrets service.
    protected $secrets;

    // Nonce parameter name.
    const NONCE = 'nonce';

    // API key parameter name.
    const KEY = 'key';

    // API key secret name.
    const KEY_SECRET = 'BITSTAMP_KEY';

    // HMAC signature parameter name.
    const SIGNATURE = 'signature';

    // Client ID parameter name.
    const CLIENT_ID = 'client_id';

    const CLIENT_ID_SECRET = 'BITSTAMP_CLIENT_ID';

    // API key secret parameter name.
    const SECRET = 'secret';

    const SECRET_SECRET = 'BITSTAMP_SECRET';

    /**
     * Handles DI.
     *
     * @param \AppBundle\Secrets $secrets
     *   Secrets service.
     */
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
        $microtime = explode(' ', microtime());
        $nonce = $microtime[1] . substr($microtime[0], 2, 6);

        $this->nonce = (int) $nonce;
    }

    protected function nonce()
    {
        return (string) (int) $this->nonce;
    }

    /**
     * Ensures the secret API key is set as per Bitstamp API docs.
     */
    protected function key()
    {
        return (string) $this->secrets->get(self::KEY_SECRET);
    }

    /**
     * Ensures the HMAC signature is set as per Bitstamp API docs.
     *
     * Signature is a HMAC-SHA256 encoded message containing: nonce, client ID
     * and API key. The HMAC-SHA256 code must be generated using a secret key
     * that was generated with your API key. This code must be converted to it's
     * hexadecimal representation (64 uppercase characters).
     *
     * @see $this->nonce()
     * @see https://www.bitstamp.net/api/
     *
     * @return string
     *   An HMAC signature as per the Bitstamp API docs.
     */
    protected function ensureSignature()
    {
        $data = $this->nonce() . $this->secrets->get(self::CLIENT_ID_SECRET) . $this->secrets->get(self::KEY_SECRET);

        return (string) strtoupper(hash_hmac('sha256', $data, $this->secrets->get(self::SECRET_SECRET)));
    }

    /**
     * Handles required authentication parameters for Bitstamp API security.
     *
     * @return string[]
     *   An associative array matching Bitstamp required private API key/value
     *   pairs.
     */
    public function getAuthParams()
    {
        // Generate a new nonce for this set of params.
        $this->generateNonce();

        $params = [
            $this::NONCE => $this->nonce(),
            $this::KEY => $this->key(),
            $this::SIGNATURE => $this->ensureSignature(),
        ];

        // Ensure strings.
        $params = array_map(function($param) {
            return (string) $param;
        }, $params);

        return $params;
    }
}
