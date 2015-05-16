<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\Buy;

/**
 * Tests the Bitstamp EURUSD class.
 */
class BuyTest extends PrivateAPITest
{
    protected $endpoint = 'balance';
    protected $servicename = 'bitstamp.balance';
    // @todo replace these samples with real data.
    protected $sample = 'foo';
    protected $sample2 = 'bar';

    /**
     * Returns a Ticker object with Mocks preconfigured.
     *
     * @return Ticker
     */
    protected function getClass()
    {
          return new Buy($this->client(), $this->getMockAuthenticator());
    }
}
