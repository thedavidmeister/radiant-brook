<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;
use Symfony\Component\Finder\Finder;

abstract class PrivateBitstampAPI extends BitstampAPI
{

  protected $_nonce;

  protected $keysArray;

  protected $params;

  protected $datetime;

  const NONCE = 'nonce';

  const KEY = 'key';

  const SIGNATURE = 'signature';

  const CLIENT_ID = 'client_id';

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
  protected function nonce() {
    if (!isset($this->_nonce)) {
      // Generate a nonce as microtime, with as-string handling to avoid problems
      // with 32bits systems.
      $mt = explode(' ', microtime());
      $this->_nonce = $mt[1] . substr($mt[0], 2, 6);
    }
    // @todo - sleep for one microsecond to ensure we never repeat a nonce.
    return $this->_nonce;
  }

  protected function ensureNonce() {
    $this->params[$this::NONCE] = $this->nonce();
    return $this;
  }

  protected function ensureKey() {
    $this->params[$this::KEY] = $this->secrets()[$this::KEY];
    return $this;
  }

  protected function ensureSignature() {
    $data = $this->nonce() . $this->secrets()[$this::CLIENT_ID] . $this->secrets()[$this::KEY];
    $this->params[$this::SIGNATURE] = strtoupper(hash_hmac('sha256', $data, $this->secrets()[$this::SECRET]));
    return $this;
  }

  protected function secrets() {
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

  protected function authenticate() {
    $this
      ->ensureKey()
      ->ensureNonce()
      ->ensureSignature();
  }

  public function datetime() {
    return $this->datetime;
  }

  public function execute() {
    foreach ($this->requiredParams() as $required) {
      if (!isset($this->params[$required])) {
        throw new \Exception('Required parameter ' . $required . ' must be sent for endpoint ' . $this->endpoint());
      }
    }

    $this->authenticate();
    $this->datetime = new \DateTime();
    // @todo - add logging!
    $result = $this->client->post($this->url(), ['body' => $this->params])->json();
    return $result;
  }

  public function setParam($name, $value) {
    if (in_array($name, ['key', 'signature', 'nonce'])) {
      throw new \Exception('You cannot directly set authentication parameters');
    }

    $this->params[$name] = $value;

    return $this;
  }

  public function getParams() {
    return $this->params;
  }
}
