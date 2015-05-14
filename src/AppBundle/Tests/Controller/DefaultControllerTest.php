<?php

namespace AppBundle\Tests\Controller;

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
     * @return client
     */
    protected function createAuthClient()
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ]);
    }

    /**
     * Asserts no access for unauthenticated users.
     *
     * @param string $uri
     *   The URI to test.
     */
    public function assertNoAnonymousAccess($uri)
    {
        $client = static::createClient();

        $client->request('GET', $uri);

        // We are not authorized so should get 401.
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    /**
     * Asserts the navbar on the page.
     *
     * @param crawler $crawler
     *   The crawler created from a client.
     */
    public function assertNav($crawler)
    {
        $this->assertTrue($crawler->filter('a[href="/trade/order_book"]:contains("Order Book data")')->count() > 0);
        $this->assertTrue($crawler->filter('a[href="/trade/trade"]:contains("Trade data")')->count() > 0);
    }

    /**
     * Runs a set of tests looking for text on the page and auth checks.
     */
    protected function standardTests($uri, $expecteds)
    {
        $this->assertNoAnonymousAccess($uri);

        $authClient = $this->createAuthClient();

        $crawler = $authClient->request('GET', $uri);

        $this->assertEquals(200, $authClient->getResponse()->getStatusCode());

        $this->assertNav($crawler);

        foreach ($expecteds as $expected) {
            $this->assertTrue($crawler->filter('html:contains("' . $expected . '")')->count() > 0);
        }
    }

    /**
     * Tests that /trade/trade is producing trade pair suggestions.
     *
     * @group slow
     */
    public function testTrade()
    {
        $uri = '/trade/trade';

        $expecteds = [
            '-Bids-',
            'bid/buy USD Base Volume',
            'bid/buy BTC Volume',
            'bid/buy USD Price',
            'bid/buy USD Volume post fees',
            '-Asks-',
            'ask/sell USD Base Volume',
            'ask/sell BTC Volume',
            'ask/sell USD Price',
            'ask/sell USD Volume post fees',
            '-Diff-',
            'BTC Profit',
            'BTC Profit USD value (midpoint)',
            'USD Profit',
            'Is profitable',
            'Has dupes',
            'Is valid trade',
            '-Dupes-',
            'Dupe bid range',
            'Dupe bids',
            'Dupe ask range',
            'Dupe asks',
            '-Facts-',
            'Fees',
            'Book time',
            'Balance time',
            'Open orders time',
        ];

        $this->standardTests($uri, $expecteds);
    }

    /**
     * Tests that /trade/orderbook is producing stats.
     *
     * @group slow
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
