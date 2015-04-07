<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\API\Bitstamp\OrderBook;

class DefaultController extends Controller
{
    /**
     * @Route("raw/{endpoint}", name="raw")
     */
    public function rawAction($endpoint) {
      switch ($endpoint) {
        case 'ticker':
          $raw = new \AppBundle\API\Bitstamp\Ticker();
          break;

        case 'order_book':
          $raw = new OrderBook();
          break;

        case 'transactions':
          $raw = new \AppBundle\API\Bitstamp\Transactions();
          break;

        case 'eur_usd':
          $raw = new \AppBundle\API\Bitstamp\EURUSD();
          break;

      }
      ldd($raw->data());
    }

    /**
     * @Route("order_book", name="order_book")
     */
    public function orderBookAction() {
      $ob = new OrderBook();

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
          '-' => ['-', '-'],
        ];
      }

      return $this->render('AppBundle::order-book.html.twig', [
        'stats' => $stats,
      ]);
    }

}
