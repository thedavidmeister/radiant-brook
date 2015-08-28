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
     *
     * @slowThreshold 5000
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
     *
     * @slowThreshold 5000
     */
    public function testBody()
    {
        $current = $this->extractAPIDocumentation($this->response()->getBody());
        $hash = md5($current);
        // Hashes are easier to compare than big strings or arrays.
        $this->assertEquals($this->expectedExtractHash(), $hash, 'Hashes do not match. expected: ' . $this->expectedExtractHash() . ' actual: ' . $hash . ' full text: ' . $current);
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

        $text = $nodes->item(0)->nodeValue;

        // Split on word boundaries.
        $text = preg_split("@\b@", $text);
        // Trim words.
        $text = array_map('trim', $text);
        // Kill empty strings.
        $text = array_filter($text);
        // Kill punctuation not attached to a word.
        $text = array_filter($text, function($word) {
            return !preg_match('@^[-=+?.:/;()\'," \s].*$@', $word);
        });

        $text = implode(' ', $text);

        return $text;
    }

    /**
     * Sampled extracted output from Bitstamp API docs.
     *
     * Last sampled: 2015-8-2.
     *
     * @return string
     *   The sampled HTML from the Bitstamp API docs.
     */
    protected function expectedExtractHash()
    {
        $extract = 'Overview   Order Book   API    HTTP API   Websocket APIAPIWhat Is API Bitstamp application programming interface API allows our clients to access and control their accounts using custom written software Request limitsDo not make more than 600 request per 10 minutes or we will ban your IP address For real time data please refer to the websocket API Public Data FunctionsTickerGET https www bitstamp net api ticker Returns JSON dictionary last last BTC pricehigh last 24 hours price highlow last 24 hours price lowvwap last 24 hours volume weighted average price vwapvolume last 24 hours volumebid highest buy orderask lowest sell orderHourly tickerGET https www bitstamp net api ticker_hour Returns JSON dictionary like https www bitstamp net api ticker but calculated values are from within an hour Order bookGET https www bitstamp net api order_book Returns JSON dictionary with bids and asks Each is a list of open orders and each order is represented as a list of price and amount TransactionsGET https www bitstamp net api transactions Params time time frame for transaction export minute 1 minute hour 1 hour day 1 day Default hour Returns descending JSON list of transactions Every transaction dictionary contains date unix timestamp date and timetid transaction idprice BTC priceamount BTC amounttype buy or sell buy 1 sell EUR USD conversion rateGET https www bitstamp net api eur_usd Returns JSON dictionary buy buy conversion ratesell sell conversion rateAPI authenticationAll private API calls require authentication You need to provide 3 parameters to authenticate a request API keyNonceSignatureAPI keyTo get an API key go to Account Security and then API Access Set permissions and click Generate key NonceNonce is a regular integer number It must be increasing with every request you make Read more about it here Example if you set nonce to 1 in your first request you must set it to at least 2 in your second request You are not required to start with 1 A common practice is to use unix time for that parameter SignatureSignature is a HMAC SHA256 encoded message containing nonce customer ID can be found here and API key The HMAC SHA256 code must be generated using a secret key that was generated with your API key This code must be converted to it s hexadecimal representation 64 uppercase characters Example Python message nonce customer_id api_key signature hmac new API_SECRET msg message digestmod hashlib sha256 hexdigest upper Private FunctionsAccount balancePOST https www bitstamp net api balance Params key API keysignature signaturenonce nonceReturns JSON dictionary usd_balance USD balancebtc_balance BTC balanceusd_reserved USD reserved in open ordersbtc_reserved BTC reserved in open ordersusd_available USD available for tradingbtc_available BTC available for tradingfee customer trading feeThis API call is cached for 10 seconds User transactionsPOST https www bitstamp net api user_transactions Params key API keysignature signaturenonce nonceoffset skip that many transactions before beginning to return results Default limit limit result to that many transactions Default 100 Maximum 1000 sort sorting by date and time asc ascending desc descending Default desc Returns descending JSON list of transactions Every transaction dictionary contains datetime date and timeid transaction idtype transaction type deposit 1 withdrawal 2 market trade usd USD amountbtc BTC amountfee transaction feeorder_id executed order idOpen ordersPOST https www bitstamp net api open_orders Params key API keysignature signaturenonce nonceReturns JSON list of open orders Each order is represented as dictionary id order iddatetime date and timetype buy or sell buy 1 sell price priceamount amountThis API call is cached for 10 seconds Order statusPOST https www bitstamp net api order_status Params key API keysignature signaturenonce nonceid order IDReturns JSON dictionary representing order status In Queue Open or Finishedtransactions Each transaction in dictionary is represented as a list of tid usd price fee and btc Cancel orderPOST https www bitstamp net api cancel_order Params key API keysignature signaturenonce nonceid order IDReturns true if order has been found and canceled Cancel all ordersPOST https www bitstamp net api cancel_all_orders Params key API keysignature signaturenonce nonceThis call will cancel all open orders Returns true if all orders have been canceled false if it failed Buy limit orderPOST https www bitstamp net api buy Params key API keysignature signaturenonce nonceamount amountprice pricelimit_price sell if executed priceReturns JSON dictionary representing order id order iddatetime date and timetype buy or sell buy 1 sell price priceamount amountSell limit orderPOST https www bitstamp net api sell Params key API keysignature signaturenonce nonceamount amountprice pricelimit_price buy if executed priceReturns JSON dictionary representing order id order iddatetime date and timetype buy or sell buy 1 sell price priceamount amountWithdrawal requestsPOST https www bitstamp net api withdrawal_requests Params key API keysignature signaturenonce nonceReturns JSON list of withdrawal requests Each request is represented as dictionary id order iddatetime date and timetype SEPA 1 bitcoin 2 WIRE transfer amount amountstatus open 1 in process 2 finished 3 canceled 4 failed data additional withdrawal request dataBitcoin withdrawalPOST https www bitstamp net api bitcoin_withdrawal Params key API keysignature signaturenonce nonceamount bitcoin amountaddress bitcoin addressReturns JSON dictionary if successful id withdrawal idBitcoin deposit addressPOST https www bitstamp net api bitcoin_deposit_address Params key API keysignature signaturenonce nonceReturns your bitcoin deposit address Unconfirmed bitcoin depositsPOST https www bitstamp net api unconfirmed_btc Params key API keysignature signaturenonce nonceReturns JSON list of unconfirmed bitcoin transactions Each transaction is represented as dictionary amount bitcoin amountaddress deposit address usedconfirmations number of confirmationsThis API call is cached for 60 seconds Ripple withdrawalPOST https www bitstamp net api ripple_withdrawal Params key API keysignature signaturenonce nonceamount currency amountaddress bitcoin addresscurrency currencyReturns true if successful Ripple deposit addressPOST https www bitstamp net api ripple_address Params key API keysignature signaturenonce nonceReturns your ripple deposit address';

        return 'd160b97eb0073b02c6e426e6ae9e0018';
    }
}
