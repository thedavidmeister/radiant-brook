<?php

namespace AppBundle\Tests\Controller;

use Respect\Validation\Validator as v;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests for DefaultController.
 */
class DefaultControllerTest extends WebTestCase
{
    /**
     * Provides a client authenticated with the test admin.
     *
     * @see config_test.yml
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createAuthClient()
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ]);
    }

    /**
     * Provides an anonymous client.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function createAnonClient()
    {
        return static::createClient();
    }

    /**
     * Asserts no access for unauthenticated users.
     *
     * @param string $uri
     *   The URI to test.
     *
     * @see standardTests
     */
    protected function assertNoAnonymousAccess($uri)
    {
        v::stringType()->check($uri);

        $client = $this->createAnonClient();

        $client->request('GET', $uri);

        // We are not authorized so should get 401.
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Asserts that authenticated users see a 200 for the given URI.
     *
     * @see standardTests
     */
    protected function assertAuth200($uri)
    {
        $authClient = $this->createAuthClient();

        $authClient->request('GET', $uri);

        // Help debug 500 errors.
        if (200 !== $authClient->getResponse()->getStatusCode()) {
            fwrite(STDERR, print_r($authClient->getResponse(), true));
        }

        print 'foooo';

        $this->assertSame(200, $authClient->getResponse()->getStatusCode());
    }

    /**
     * Asserts specific page elements on the given URI for authenticated users.
     *
     * @see standardTests
     */
    protected function assertAuthPageElements($uri, array $expecteds)
    {
        $authClient = $this->createAuthClient();

        $crawler = $authClient->request('GET', $uri);

        // Assert the navbar on the page.
        $this->assertGreaterThan(0, $crawler->filter('a[href="/trade/order_book"]:contains("Order Book data")')->count());
        $this->assertGreaterThan(0, $crawler->filter('a[href="/trade/trade"]:contains("Trade data")')->count());

        foreach ($expecteds as $expected) {
            $this->assertGreaterThan(0, $crawler->filter('html:contains("' . $expected . '")')->count(), $expected . ' is missing from the page.');
        }
    }

    /**
     * Runs a set of tests looking for text on the page and auth checks.
     *
     * @param string $uri
     *   The URI to check.
     *
     * @param string[] $expecteds
     *   Strings we expect to see on the page when authenticated.
     */
    protected function standardTests($uri, $expecteds)
    {
        v::stringType()->check($uri);
        v::each(v::stringType()->notEmpty())->check($expecteds);

        $this->assertNoAnonymousAccess($uri);

        $this->assertAuth200($uri);

        $this->assertAuthPageElements($uri, $expecteds);
    }

    /**
     * Tests that / produces a 200.
     *
     * @covers AppBundle\Controller\DefaultController::indexAction
     * @group stable
     * @group slow
     */
    public function testIndex()
    {
        $uri = '/';

        $anon = $this->createAnonClient();
        $anon->request('GET', $uri);
        $this->assertEquals(200, $anon->getResponse()->getStatusCode());

        $auth = $this->createAuthClient();
        $auth->request('GET', $uri);
        $this->assertEquals(200, $auth->getResponse()->getStatusCode());
    }

    /**
     * Tests that /trade/trade is producing trade pair suggestions.
     *
     * @covers AppBundle\Controller\DefaultController::tradeAction
     * @group slow
     * @group requiresAPIKey
     * @group stable
     *
     * @slowThreshold 5000
     */
    public function testTrade()
    {
        $uri = '/trade/trade';

        $expecteds = [
            '-Facts-',
            'Fees bids multiplier',
            'Fees asks multiplier',
            'Is trading',
            '-Trade proposals-',
            'bidUSDPrice',
            'bidUSDVolumeBase',
            'bidUSDVolume',
            'bidUSDVolumePlusFees',
            'bidBTCVolume',
            'askUSDPrice',
            'askUSDVolumeCoverFees',
            'askUSDVolumePostFees',
            'askBTCVolume',
            'profitBTC',
            'minProfitBTC',
            'profitUSD',
            'minProfitUSD',
            'isProfitable',
            'isValid',
            'isCompulsory',
            'isFinal',
            'reasons',
        ];

        $this->standardTests($uri, $expecteds);
    }

    /**
     * Tests that /trade/orderbook is producing stats.
     *
     * @covers AppBundle\Controller\DefaultController::orderBookAction
     * @group slow
     * @group stable
     *
     * @slowThreshold 5000
     */
    public function testOrderBook()
    {
        $uri = '/trade/order_book';

        // Check all the desired stats.
        $expecteds = [
            'bids min',
            'bids max',
            'bids volume',
            'bids 0.01%',
            'bids 0.1%',
            'bids 1%',
            'bids Q1',
            'bids median',
            'bids Q2',
            'bids 99%',
            'bids 99.9%',
            'bids 99.99%',
            'bids total cap',
            'bids 0.01% cap',
            'bids 0.1% cap',
            'bids 1% cap',
            'bids 25% cap',
            'bids 50% cap',
            'bids 75% cap',
            'bids 99% cap',
            'bids 99.9% cap',
            'bids 99.99% cap',
            'asks min',
            'asks max',
            'asks volume',
            'asks 0.01%',
            'asks 0.1%',
            'asks 1%',
            'asks Q1',
            'asks median',
            'asks Q2',
            'asks 99%',
            'asks 99.9%',
            'asks 99.99%',
            'asks total cap',
            'asks 0.01% cap',
            'asks 0.1% cap',
            'asks 1% cap',
            'asks 25% cap',
            'asks 50% cap',
            'asks 75% cap',
            'asks 99% cap',
            'asks 99.9% cap',
            'asks 99.99% cap',
        ];

        $this->standardTests($uri, $expecteds);
    }
}
