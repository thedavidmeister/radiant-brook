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

      return $this->render('AppBundle::order-book.html.twig', [
        'stats' => [
          'min bid' => $ob->min($ob->bids()),
          'max bid' => $ob->max($ob->bids()),
          'bids volume' => $ob->totalVolume($ob->bids()),
          'min ask' => $ob->min($ob->asks()),
          'max ask' => $ob->max($ob->asks()),
        ],
      ]);
    }

}
