<?php

namespace AppBundle\Tests\API\Bitstamp\PublicAPI;

use AppBundle\API\Bitstamp\PublicAPI\EURUSD;

/**
 * Tests the Bitstamp EURUSD class.
 */
class EURUSDTest extends PublicAPITest
{
    protected $endpoint = 'eur_usd';
    protected $sample = '{"sell": "1.1209", "buy": "1.1321"}';
    protected $sample2 = '{"sell": "1.2209", "buy": "1.2321"}';
    protected $servicename = 'bitstamp.eur_usd';

    /**
     * Returns a Ticker object with Mocks preconfigured.
     *
     * @return Ticker
     */
    protected function getClass()
    {
          return new EURUSD($this->client());
    }
}
