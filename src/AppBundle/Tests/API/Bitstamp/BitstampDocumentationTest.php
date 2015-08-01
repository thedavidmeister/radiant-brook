<?php

namespace AppBundle\Tests\API\Bitstamp;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use GuzzleHttp\Client;
use Masterminds\HTML5;

/**
 * Tests that the Bitstamp API documentation has not changed.
 */
class BitstampDocumentationTest extends WebTestCase
{
    const BITSTAMP_DOCUMENTATION_URL = 'https://www.bitstamp.net/api/';

    /**
    * Fetch and cache a response from the API documentation.
    */
    protected function response()
    {
        if (!isset($this->response)) {
              $client = new Client();
              $this->response = $client->get(self::BITSTAMP_DOCUMENTATION_URL);
        }

        return $this->response;
    }
    protected $response;

    /**
    * Tests that the API documentation is responding as 200.
    *
    * @group stable
    * @group slow
    */
    public function testStatusCode()
    {
        $this->assertEquals(200, $this->response()->getStatusCode());
    }

    /**
    * Tests that the expected and current body represents the same API docs.
    *
    * @group stable
    * @group slow
    */
    public function testBody()
    {
        $expected = $this->extractAPIDocumentation($this->expectedBody());
        $current = $this->extractAPIDocumentation($this->response()->getBody());
        $this->assertEquals($expected, $current);
    }

    /**
    * Given an HTML body, extracts the relevant API documentation text.
    *
    * @param mixed $html
    *   Anything that can be cast to a valid HTML string.
    *
    * @return string
    *   API documentation text string, without HTML.
    */
    protected function extractAPIDocumentation($html)
    {
        $html5 = new HTML5();
        $d = $html5->loadHTML((string) $html);

        $xpath = new \DomXPath($d);
        $class = 'main_content';
        $nodes = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $class . " ')]");

        return $nodes->item(0)->nodeValue;
    }

    /**
    * Sampled HTML output from Bitstamp API docs.
    *
    * Last sampled: 2015-8-2.
    *
    * @return string
    *   The sampled HTML from the Bitstamp API docs.
    */
    protected function expectedBody()
    {
        $raw = '<!DOCTYPE html>
<html lang="en">
    <!-- POMEMBNO! tu mora biti pravilna koda jezika -->
    <head>
        <meta charset="utf-8">
        <title>HTTP API - Bitstamp</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="description" content="European based bitcoin exchange" />
        <meta name="keywords" content="bitcoin" />
        <link rel="stylesheet" href="/s/css/main.12.css?v=10" type="text/css" media="screen" />
        <link rel="stylesheet" href="/s/css/main_responsive.2.css?v=10" type="text/css" media="screen" />
        <!--  <link rel="stylesheet" href="/s/css/tradeview.css" type="text/css" media="screen" /> -->
        <link rel="alternate" type="application/rss+xml" title="Bitstamp News" href="https://www.bitstamp.net/news/feed/" />
        <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
        <!--[if lt IE 9]>
        <script src="http://html5shim.googlecode.com/svn/trunk/html5.js">
        </script>
        <![endif]-->
        <!-- Icons -->
        <link rel="shortcut icon" href="/s/icons/favicon.ico" />
        <!-- Fonts -->
        <link href=\'https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800\' rel=\'stylesheet\' type=\'text/css\'>
        <link href=\'https://fonts.googleapis.com/css?family=Roboto:400,300,500,700\' rel=\'stylesheet\' type=\'text/css\'>
        <link href=\'https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700\' rel=\'stylesheet\' type=\'text/css\'>
        <script>var STX_namespaced=true;</script>
    </head>
    <body>
        <div class="responsive_test">
        </div>
        <!-- Top Bar -->
        <div class="top_bar responsive_hide">
            <div class="container">
                <!-- Left -->
                <div class="left">
                    <ul class="top_bar_list">
                        <li class="header">Bitcoin price:</li>
                        <li class="last">$279.16</li>
                    </ul>
                </div>
                <!-- end Left -->
                <!-- Right -->
                <div class="right">
                    <ul class="top_bar_list">
                        <li>
                            <a href="/account/login/">Login</a>
                        </li>
                        <!-- POMEMBNO! aktiven link mora imeti dodan class "active" na "a" element -->
                        <li class="last">
                            <a href="/account/register/">Open an account</a>
                        </li>
                    </ul>
                </div>
                <!-- end Right -->
            </div>
            <!-- end container -->
        </div>
        <!-- end Top Bar -->
        <!-- Main Header -->
        <header class="main_header clearfix">
            <div class="container clearfix">
                <h1>
                <a href="/">Bitstamp</a>
                </h1>
                <a class="collapse_trigger" id="collapse_trigger">Menu</a>
                <!-- Main Nav -->
                <nav class="main_nav">
                    <ul>
                        <li>
                            <a href="/">Home</a>
                        </li>
                        <li>
                            <a href="/account/balance/">Account</a>
                        </li>
                        <!-- POMEMBNO! aktiven link mora imeti dodan class "active" na "a" element -->
                        <li>
                            <a href="/market/order/instant/">Buy / Sell</a>
                        </li>
                        <li>
                            <a href="/market/tradeview/">Tradeview</a>
                        </li>
                        <li>
                            <a href="/account/deposit/">Deposit</a>
                        </li>
                        <li>
                            <a href="/account/withdraw/">Withdrawal</a>
                        </li>
                    </ul>
                    <ul class="responsive_show">
                        <li>
                            <a href="/account/login/">Login</a>
                        </li>
                        <li>
                            <a href="/account/register/">Open an account</a>
                        </li>
                    </ul>
                </nav>
                <!-- end Main Nav -->
            </div>
            <!-- end container -->
            <div class="shadow">
            </div>
            <a class="collapse_trigger_menu" id="collapse_trigger_menu">
                <span>
                </span>Show menu</a>
            </header>
            <!-- end Main Header -->
            <!-- Cell -->
            <div class="cell">
                <div class="container">
                    <!-- Main Container -->
                    <div class="main_content clearfix">
                        <div class="menu">
                            <ul class="default_menu">
                                <li>
                                    <a href="/" id="template_text" class="template_toogle_class">Overview<span>&nbsp;</span>
                                </a>
                            </li>
                            <li>
                                <a href="/market/order_book/" id="template_text" class="template_toogle_class">Order Book<span>&nbsp;</span>
                            </a>
                        </li>
                        <li class="active second_level">
                            <a href="/api/" id="template_text" class="template_toogle_class">API<span>&nbsp;</span>
                        </a>
                        <ul class="clearfix">
                            <li class="active">
                                <a href="/api/">
                                    <span>&nbsp;</span>HTTP API</a>
                                </li>
                                <li>
                                    <a href="/websocket/">
                                        <span>&nbsp;</span>Websocket API</a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                    <div class="content">
                        <header class="clearfix">
                            <h1>API</h1>
                        </header>
                        <h2>What Is API?</h2>
                        <p>Bitstamp application programming interface (API) allows our clients to access and control their accounts, using custom written software.</p>
                        <h2>Request limits</h2>
                        <p>Do not make more than 600 request per 10 minutes or we will ban your IP address. For real time data please refer to the <a href="/websocket/">websocket API</a>.</p>
                        <h2>Public Data Functions</h2>
                        <h4>Ticker</h4>
                        <p>
                            <i>GET https://www.bitstamp.net/api/ticker/</i>
                        </p>
                        <p>Returns JSON dictionary:</p>
                        <ul>
                            <li>last - last BTC price</li>
                            <li>high - last 24 hours price high</li>
                            <li>low - last 24 hours price low</li>
                            <li>vwap - last 24 hours volume weighted average price: <a href="http://en.wikipedia.org/wiki/Volume-weighted_average_price" target="_blank">vwap</a>
                        </li>
                        <li>volume - last 24 hours volume</li>
                        <li>bid - highest buy order</li>
                        <li>ask - lowest sell order</li>
                    </ul>
                    <h4>Hourly ticker</h4>
                    <p>
                        <i>GET https://www.bitstamp.net/api/ticker_hour/</i>
                    </p>
                    <p>Returns JSON dictionary like https://www.bitstamp.net/api/ticker/, but calculated values are from within an hour.</p>
                    <h4>Order book</h4>
                    <p>
                        <i>GET https://www.bitstamp.net/api/order_book/</i>
                    </p>
                    <p>Returns JSON dictionary with "bids" and "asks". Each is a list of open orders and each order is represented as a list of price and amount.</p>
                    <h4>Transactions</h4>
                    <p>
                        <i>GET https://www.bitstamp.net/api/transactions/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>time - time frame for transaction export ("minute" - 1 minute, "hour" - 1 hour). Default: hour.</li>
                    </ul>
                    <p>Returns descending JSON list of transactions. Every transaction (dictionary) contains:</p>
                    <ul>
                        <li>date - unix timestamp date and time</li>
                        <li>tid - transaction id</li>
                        <li>price - BTC price</li>
                        <li>amount - BTC amount</li>
                        <li>type - buy or sell (0 - buy; 1 - sell)</li>
                    </ul>
                    <h4>EUR/USD conversion rate</h4>
                    <p>
                        <i>GET https://www.bitstamp.net/api/eur_usd/</i>
                    </p>
                    <p>Returns JSON dictionary:</p>
                    <ul>
                        <li>buy - buy conversion rate</li>
                        <li>sell - sell conversion rate</li>
                    </ul>
                    <h1>API authentication</h1>
                    <p>All private API calls require authentication. You need to provide 3 parameters to authenticate a request:</p>
                    <ul>
                        <li>API key</li>
                        <li>Nonce</li>
                        <li>Signature</li>
                    </ul>
                    <h4>API key</h4>
                    <p>To get an API key, go to "Account", "Security" and then "API Access". Set permissions and click "Generate key".</p>
                    <h4>Nonce</h4>
                    <p>Nonce is a regular integer number. It must be increasing with every request you make. Read more about it <a href="http://en.wikipedia.org/wiki/Cryptographic_nonce" target="_blank">here</a>. Example: if you set nonce to 1 in your first request, you must set it to at least 2 in your second request. You are not required to start with 1. A common practice is to use <a href="http://en.wikipedia.org/wiki/Unix_time" target="_blank">unix time</a> for that parameter.</p>
                    <h4>Signature</h4>
                    <p>Signature is a HMAC-SHA256 encoded message containing: nonce, customer ID (can be found <a href="/account/balance/" target="_blank">here</a>) and API key. The HMAC-SHA256 code must be generated using a secret key that was generated with your API key. This code must be converted to it\'s hexadecimal representation (64 uppercase characters).</p>
                    <p>
                        <i>Example (Python):<br />
                        signature = hmac.new(API_SECRET, msg=message, digestmod=hashlib.sha256).hexdigest().upper()
                        </i>
                    </p>
                    <h1>Private Functions</h1>
                    <h4>Account balance</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/balance/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns JSON dictionary:</p>
                    <ul>
                        <li>usd_balance - USD balance</li>
                        <li>btc_balance - BTC balance</li>
                        <li>usd_reserved - USD reserved in open orders</li>
                        <li>btc_reserved - BTC reserved in open orders</li>
                        <li>usd_available- USD available for trading</li>
                        <li>btc_available - BTC available for trading</li>
                        <li>fee - customer trading fee</li>
                    </ul>
                    <p>This API call is cached for 10 seconds.</p>
                    <h4>User transactions</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/user_transactions/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>offset - skip that many transactions before beginning to return results. Default: 0.</li>
                        <li>limit - limit result to that many transactions. Default: 100. Maximum: 1000.</li>
                        <li>sort - sorting by date and time (asc - ascending; desc - descending). Default: desc.</li>
                    </ul>
                    <p>Returns descending JSON list of transactions. Every transaction (dictionary) contains:</p>
                    <ul>
                        <li>datetime - date and time</li>
                        <li>id - transaction id</li>
                        <li>type - transaction type (0 - deposit; 1 - withdrawal; 2 - market trade)</li>
                        <li>usd - USD amount</li>
                        <li>btc - BTC amount</li>
                        <li>fee - transaction fee</li>
                        <li>order_id - executed order id</li>
                    </ul>
                    <h4>Open orders</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/open_orders/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns JSON list of open orders. Each order is represented as dictionary:</p>
                    <ul>
                        <li>id - order id</li>
                        <li>datetime - date and time</li>
                        <li>type - buy or sell (0 - buy; 1 - sell)</li>
                        <li>price - price</li>
                        <li>amount - amount</li>
                    </ul>
                    <p>This API call is cached for 10 seconds.</p>
                    <h4>Order status</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/order_status/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>id - order ID</li>
                    </ul>
                    <p>Returns JSON dictionary representing order:</p>
                    <ul>
                        <li>status - In Queue, Open or Finished</li>
                        <li>transactions - Each transaction in dictionary is represented as a list of tid, usd, price, fee and btc.</li>
                    </ul>
                    <h4>Cancel order</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/cancel_order/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>id - order ID</li>
                    </ul>
                    <p>Returns \'true\' if order has been found and canceled.</p>
                    <h4>Cancel all orders</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/cancel_all_orders/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>This call will cancel all open orders.</p>
                    <p>Returns \'true\' if all orders have been canceled, false if it failed.</p>
                    <h4>Buy limit order</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/buy/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>amount - amount</li>
                        <li>price - price</li>
                        <li>limit_price - sell if executed price</li>
                    </ul>
                    <p>Returns JSON dictionary representing order:</p>
                    <ul>
                        <li>id - order id</li>
                        <li>datetime - date and time</li>
                        <li>type - buy or sell (0 - buy; 1 - sell)</li>
                        <li>price - price</li>
                        <li>amount - amount</li>
                    </ul>
                    <h4>Sell limit order</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/sell/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>amount - amount</li>
                        <li>price - price</li>
                        <li>limit_price - buy if executed price</li>
                    </ul>
                    <p>Returns JSON dictionary representing order:</p>
                    <ul>
                        <li>id - order id</li>
                        <li>datetime - date and time</li>
                        <li>type - buy or sell (0 - buy; 1 - sell)</li>
                        <li>price - price</li>
                        <li>amount - amount</li>
                    </ul>
                    <h4>Withdrawal requests</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/withdrawal_requests/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns JSON list of withdrawal requests. Each request is represented as dictionary:</p>
                    <ul>
                        <li>id - order id</li>
                        <li>datetime - date and time</li>
                        <li>type - (0 - SEPA; 1 - bitcoin; 2 - WIRE transfer)</li>
                        <li>amount - amount</li>
                        <li>status - (0 - open; 1 - in process; 2 - finished; 3 - canceled; 4 - failed)</li>
                        <li>data - additional withdrawal request data</li>
                    </ul>
                    <h4>Bitcoin withdrawal</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/bitcoin_withdrawal/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>amount - bitcoin amount</li>
                        <li>address - bitcoin address</li>
                    </ul>
                    <p>Returns JSON dictionary if successful:</p>
                    <ul>
                        <li>id - withdrawal id</li>
                    </ul>
                    <h4>Bitcoin deposit address</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/bitcoin_deposit_address/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns your bitcoin deposit address.</p>
                    <h4>Unconfirmed bitcoin deposits</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/unconfirmed_btc/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns JSON list of unconfirmed bitcoin transactions. Each transaction is represented as dictionary:</p>
                    <ul>
                        <li>amount - bitcoin amount</li>
                        <li>address - deposit address used</li>
                        <li>confirmations - number of confirmations</li>
                    </ul>
                    <p>This API call is cached for 60 seconds.</p>
                    <h4>Ripple withdrawal</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/ripple_withdrawal/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                        <li>amount - currency amount</li>
                        <li>address - bitcoin address</li>
                        <li>currency - currency</li>
                    </ul>
                    <p>Returns true if successful.</p>
                    <h4>Ripple deposit address</h4>
                    <p>
                        <i>POST https://www.bitstamp.net/api/ripple_address/</i>
                    </p>
                    <p>Params:</p>
                    <ul>
                        <li>key - API key</li>
                        <li>signature - signature</li>
                        <li>nonce - nonce</li>
                    </ul>
                    <p>Returns your ripple deposit address.</p>
                </div>
            </div>
            <!-- end Main Content -->
        </div>
        <!-- end container -->
    </div>
    <!-- end cell -->
    <!-- Foot -->
    <footer class="main_footer">
        <div class="container clearfix">
            <div class="column first responsive_hide">
                <div class="copyright_logo">
                    <div class="copyright">&copy; 2015 Bitstamp Ltd.</div>
                    <p>
                        <b>Bitstamp Ltd.</b>
                    <br />5 New Street Square<br />London EC4A 3TW<br />United Kingdom</p>
                </div>
            </div>
            <div class="column" id="contact-us">
                <h2>Contact us</h2>
                <ul>
                    <li>
                        <a href="mailto:press@bitstamp.net">press@bitstamp.net</a>
                    </li>
                    <li>
                        <a href="mailto:info@bitstamp.net">info@bitstamp.net</a>
                    </li>
                    <li class="support-header">SUPPORT</li>
                    <li>
                        <a href="mailto:support@bitstamp.net">support@bitstamp.net</a>
                    </li>
                    <li>
                        <a title="Available from 8:00 GMT till 19:00 GMT, Saturdays from 8:00 GMT till 11:00 GMT">+44 020 8133 5474</a>
                    </li>
                </ul>
                <div class="column social">
                    <ul>
                        <li>
                            <a href="http://www.facebook.com/Bitstamp" target="_blank" class="icon social-facebook" title="Bitstamp on Facebook">Bitstamp on Facebook</a>
                        </li>
                        <li>
                            <a href="http://www.linkedin.com/company/bitstamp" target="_blank" class="icon social-linkedin" title="Bitstamp on LinkedIn">Bitstamp on LinkedIn</a>
                        </li>
                        <li>
                            <a href="http://twitter.com/Bitstamp" target="_blank" class="icon social-twitter" title="Bitstamp on Twitter">Bitstamp on Twitter</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="column">
                <h2>Data</h2>
                <ul>
                    <li>
                        <a href="/">Overview</a>
                    </li>
                    <li>
                        <a href="/market/order_book/">Order Book</a>
                    </li>
                    <li>
                        <a href="/api/">API</a>
                    </li>
                </ul>
            </div>
            <div class="column">
                <h2>Help</h2>
                <ul>
                    <li>
                        <a href="/help/what-is-bitcoin/">What is bitcoin?</a>
                    </li>
                    <li>
                        <a href="/help/how-to-buy/">How to buy bitcoins?</a>
                    </li>
                    <li>
                        <a href="/help/how-to-sell/">How to sell bitcoins?</a>
                    </li>
                    <li>
                        <a href="/faq/">FAQ</a>
                    </li>
                </ul>
            </div>
            <div class="column">
                <h2>About</h2>
                <ul>
                    <li>
                        <a href="/about_us/">About us</a>
                    </li>
                    <li>
                        <a href="/news/">News</a>
                    </li>
                    <li>
                        <a href="/fee_schedule/">Fee Schedule</a>
                    </li>
                    <li>
                        <a href="/terms-of-use/">Terms of Use</a>
                    </li>
                    <li>
                        <a href="/privacy-policy/">Privacy Policy</a>
                    </li>
                    <li>
                        <a href="/aml-policy/">AML Policy</a>
                    </li>
                </ul>
            </div>
            <!-- Responsive switch -->
            <div class="responsive_switch responsive_show">
                <div class="switch_button">
                </div>
                <div class="copyright">&copy; 2015 Bitstamp Ltd.</div>
            </div>
            <!-- Responsive switch -->
        </div>
        <!-- end container -->
    </footer>
    <!-- end Foot -->
    <script type="text/javascript" charset="utf-8" src="/s/js/jquery.1.8.2.min.js">
    </script>
    <script type="text/javascript" src="/s/js/jquery.1.10.3.js">
    </script>
    <script type="text/javascript" src="/s/js/analytics.js">
    </script>
    <script type="text/javascript" src="/s/js/locd.js">
    </script>
    <script type="text/javascript" charset="utf-8" src="/s/scripts/bootstrap-tooltip.js">
    </script>
    <!-- http://twitter.github.com/bootstrap/javascript.html#tooltips -->
    <script type="text/javascript" charset="utf-8" src="/s/scripts/bootstrap-dropdown.js">
    </script>
    <!-- http://twitter.github.com/bootstrap/javascript.html#dropdowns -->
    <script type="text/javascript" charset="utf-8" src="/s/scripts/bootstrap-modal.js">
    </script>
    <!-- http://twitter.github.com/bootstrap/javascript.html#modals -->
    <script type="text/javascript" charset="utf-8" src="/s/scripts/bootstrap-transition.js">
    </script>
    <script type="text/javascript" charset="utf-8" src="/s/scripts/jquery.placeholder.min.js">
    </script>
    <!-- http://webcloud.se/code/jQuery-Placeholder/ -->
    <script type="text/javascript"  src="/s/js/highstock.js">
    </script>
    <script type="text/javascript" charset="utf-8" src="/s/scripts/jQueryRotate.2.2.js">
    </script>
    <!-- http://code.google.com/p/jqueryrotate/ -->
    <script type="text/javascript" charset="utf-8" src="/s/scripts/iphone-style-checkboxes.js">
    </script>
    <!-- http://ios-checkboxes.awardwinningfjords.com/ -->
    <script type="text/javascript" src="/s/scripts/main.13.js">
    </script>
    <script type="text/javascript">
    adroll_adv_id = "T62UVLUQIRHEPKBTYGPCUS";
    adroll_pix_id = "RY24E2KDS5H6LO5AX5Y4A6";
    (function () {
    var oldonload = window.onload;
    window.onload = function(){
    __adroll_loaded=true;
    var scr = document.createElement("script");
    var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
    scr.setAttribute(\'async\', \'true\');
    scr.type = "text/javascript";
    scr.src = host + "/j/roundtrip.js";
    ((document.getElementsByTagName(\'head\') || [null])[0] ||
    document.getElementsByTagName(\'script\')[0].parentNode).appendChild(scr);
    if(oldonload){oldonload()}};
    }());
    </script>
</body>
</html>';

        return $raw;
    }
}
