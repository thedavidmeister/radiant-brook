<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\BitstampTradePairs;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("raw/{endpoint}", name="raw")
     */
    public function rawAction($endpoint) {
      $endpoints = ['ticker', 'order_book', 'transactions', 'eur_usd'];
      if (!in_array($endpoint, $endpoints)) {
        throw new \Exception("Invalid endpoint $endpoint");
      }
      $raw = $this->get("bitstamp.$endpoint");
      ldd($raw->data());
    }

    /**
     * @Route("trade/order_book", name="order_book")
     */
    public function orderBookAction() {
      $ob = $this->get('bitstamp.order_book');

      $stats = array();
      foreach (['bids', 'asks'] as $list) {
        $stats += [
          "$list min" => $ob->$list()->min(),
          "$list max" => $ob->$list()->max(),
          "$list volume" => ['n/a', $ob->$list()->totalVolume()],
          "$list 0.01%" => $ob->$list()->percentile(0.0001),
          "$list 0.1%" => $ob->$list()->percentile(0.001),
          "$list 1%" => $ob->$list()->percentile(0.01),
          "$list Q1" => $ob->$list()->percentile(0.25),
          "$list median" => $ob->$list()->percentile(0.5),
          "$list Q2" => $ob->$list()->percentile(0.75),
          "$list 99%" => $ob->$list()->percentile(0.99),
          "$list 99.9%" => $ob->$list()->percentile(0.999),
          "$list 99.99%" => $ob->$list()->percentile(0.9999),
          "$list total cap" => ['n/a', $ob->$list()->totalCap()],
          "$list 0.01% cap" => $ob->$list()->percentCap(0.0001),
          "$list 0.1% cap" => $ob->$list()->percentCap(0.001),
          "$list 1% cap" => $ob->$list()->percentCap(0.01),
          "$list 25% cap" => $ob->$list()->percentCap(0.25),
          "$list 50% cap" => $ob->$list()->percentCap(0.50),
          "$list 75% cap" => $ob->$list()->percentCap(0.75),
          "$list 99% cap" => $ob->$list()->percentCap(0.99),
          "$list 99.9% cap" => $ob->$list()->percentCap(0.999),
          "$list 99.99% cap" => $ob->$list()->percentCap(0.9999),
          '-' => ['-', '-'],
        ];
      }

      return $this->render('AppBundle::order-book.html.twig', [
        'stats' => $stats,
      ]);
    }

    /**
     * @Route("trade/trade", name="trade")
     */
    public function tradeIndex(Request $request) {
      $tp = $this->get('bitstamp.trade_pairs');

      $time_format = 'Y-m-d H:i:s';

      $stats = [
        '-Bids-' => '',
        'bid/buy USD Base Volume' => $tp->volumeUSDBid(),
        'bid/buy BTC Volume' => $tp->bidBTCVolume(),
        'bid/buy USD Price' => $tp->bidPrice(),
        'bid/buy USD Volume post fees' => $tp->volumeUSDBidPostFees(),
        '-Asks-' => '',
        'ask/sell USD Base Volume' => $tp->volumeUSDAsk(),
        'ask/sell BTC Volume' => $tp->askBTCVolume(),
        'ask/sell USD Price' => $tp->askPrice(),
        'ask/sell USD Volume post fees' => $tp->volumeUSDAskPostFees(),
        '-Diff-' => '',
        'BTC Profit' => $tp->profitBTC(),
        'BTC Profit USD value (midpoint)' => $tp->profitBTC() * $tp->midprice(),
        'USD Profit' => $tp->profitUSD(),
        'Is profitable' => $tp->isProfitable() ? 'Yes' : 'No',
        'Has dupes' => $tp->hasDupes() ? 'Yes' : 'No',
        'Is valid trade' => $tp->isValid() ? 'Yes' : 'No',
        '-Dupes-' => '',
        'Dupe bid range' => $tp->bidPrice() * $tp::DUPE_RANGE_MULTIPLIER,
        'Dupe bids' => var_export($tp->dupes()['bids'], TRUE),
        'Dupe ask range' => $tp->askPrice() * $tp::DUPE_RANGE_MULTIPLIER,
        'Dupe asks' => var_export($tp->dupes()['asks'], TRUE),
        '-Facts-' => '',
        'Fees' => $tp->fee(),
        // Sticking times at the bottom is a hack to ensure that we've hit
        // the endpoints.
        'Book time' => $tp->datetime('orderBook')->format($time_format),
        'Balance time' => $tp->datetime('balance')->format($time_format),
        'Open orders time' => $tp->datetime('openOrders')->format($time_format),
      ];

      // @todo - turn this into a separate class?
      $form = $this->createFormBuilder($tp)
        ->add('save', 'submit', ['label' => 'Execute trade'])
        ->getForm();

      $form->handleRequest($request);

      if ($form->isValid()) {
        $tp->execute();
        print 'Executed trade!';
      }

      return $this->render('AppBundle::index.html.twig', [
        'stats' => $stats,
        'form' => $form->createView(),
      ]);
    }

}
