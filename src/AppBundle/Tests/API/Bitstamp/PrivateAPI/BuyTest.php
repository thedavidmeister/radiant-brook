<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

use AppBundle\API\Bitstamp\PrivateAPI\Buy;

/**
 * Tests the Bitstamp EURUSD class.
 */
class BuyTest extends PrivateAPITest
{
    protected $endpoint = 'buy';
    protected $servicename = 'bitstamp.buy';
    // @todo replace these samples with real data.
    protected $sample = '{"foo": "bar"}';
    protected $sample2 = '{"bing": "baz"}';

    /**
     * Returns a Ticker object with Mocks preconfigured.
     *
     * @return Ticker
     */
    protected function getClass()
    {
          $class = new Buy($this->client(), $this->getMockAuthenticator());
          $class->setParams(['amount' => 0.1, 'price' => 0.1]);

          return $class;
    }
}
