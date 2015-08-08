<?php

namespace AppBundle\Tests\API\Bitstamp\TradePairs;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use Money\Money;
use AppBundle\Tests\EnvironmentTestTrait;

class TradeProposalTest extends WebTestCase
{
  use EnvironmentTestTrait;

      protected function mock($class)
    {
        return $this
            ->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function fees()
    {
        return $this->mock('\AppBundle\API\Bitstamp\TradePairs\Fees');
    }

    protected function tradeProposal() {
      return new TradeProposal(['bidUSDPrice' => Money::USD(1), 'askUSDPrice' => Money::USD(100)], $this->fees());
    }

    /**
     * Test minProfitUSD().
     *
     * @group stable
     */
    public function testMinProfitUSD()
    {
      // sets, expects.
      $scenarios = [
          [0, Money::USD(0)],
          ['0', Money::USD(0)],
          ['1', Money::USD(1)],
          ['100', Money::USD(100)],
          [1, Money::USD(1)],
      ];
      $test = function($scenario) {
        $this->setEnv('BITSTAMP_MIN_USD_PROFIT', $scenario[0]);
        $this->assertEquals($scenario[1], $this->tradeProposal()->minProfitUSD());
      };
      array_walk($scenarios, $test);
    }
}
