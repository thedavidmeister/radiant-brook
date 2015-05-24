<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\BitstampTradePairs;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for AppBundle.
 */
class DefaultController extends Controller
{
    /**
     * Prints raw responses from Bitstamp endpoints.
     *
     * @Route("raw/{endpoint}", name="raw")
     *
     * @param string $endpoint
     *   The name of the Bitstamp API endpoint to hit.
     *
     * @return Response
     */
    public function rawAction($endpoint)
    {
        $endpoints = ['ticker', 'order_book', 'transactions', 'eur_usd', 'balance'];
        if (!in_array($endpoint, $endpoints)) {
            throw new \Exception('Invalid endpoint ' . $endpoint);
        }
        $raw = $this->get('bitstamp.' . $endpoint);
        ldd($raw->data());
    }

    /**
     * Just a blank index page.
     *
     * @Route("/", name="index")
     *
     * @return Response
     */
    public function indexAction()
    {
        // Just renders nothing.
        return $this->render('base.html.twig');
    }

    /**
     * Calculates summary statistics for the current order book.
     *
     * @Route("trade/order_book", name="order_book")
     *
     * @return Response
     */
    public function orderBookAction()
    {
        $ob = $this->get('bitstamp.order_book');

        $stats = array();
        foreach (['bids', 'asks'] as $list) {
            $stats += [
            $list . ' min' => [$ob->$list()->min()['usd']->getAmount(), $ob->$list()->min()['btc']->getAmount(), ''],
            $list . ' max' => [$ob->$list()->max()['usd']->getAmount(), $ob->$list()->max()['btc']->getAmount(), ''],
            $list . ' volume' => ['', $ob->$list()->totalVolume(), ''],
            $list . ' 0.01%' => [$ob->$list()->percentileBTCVolume(0.0001), '', ''],
            $list . ' 0.1%' => [$ob->$list()->percentileBTCVolume(0.001), '', ''],
            $list . ' 1%' => [$ob->$list()->percentileBTCVolume(0.01), '', ''],
            $list . ' Q1' => [$ob->$list()->percentileBTCVolume(0.25), '', ''],
            $list . ' median' => [$ob->$list()->percentileBTCVolume(0.5), '', ''],
            $list . ' Q2' => [$ob->$list()->percentileBTCVolume(0.75), '', ''],
            $list . ' 99%' => [$ob->$list()->percentileBTCVolume(0.99), '', ''],
            $list . ' 99.9%' => [$ob->$list()->percentileBTCVolume(0.999), '', ''],
            $list . ' 99.99%' => [$ob->$list()->percentileBTCVolume(0.9999), '', ''],
            $list . ' total cap' => ['', '', $ob->$list()->totalCap()],
            $list . ' 0.01% cap' => [$ob->$list()->percentileCap(0.0001), '', ''],
            $list . ' 0.1% cap' => [$ob->$list()->percentileCap(0.001), '', ''],
            $list . ' 1% cap' => [$ob->$list()->percentileCap(0.01), '', ''],
            $list . ' 25% cap' => [$ob->$list()->percentileCap(0.25), '', ''],
            $list . ' 50% cap' => [$ob->$list()->percentileCap(0.50), '', ''],
            $list . ' 75% cap' => [$ob->$list()->percentileCap(0.75), '', ''],
            $list . ' 99% cap' => [$ob->$list()->percentileCap(0.99), '', ''],
            $list . ' 99.9% cap' => [$ob->$list()->percentileCap(0.999), '', ''],
            $list . ' 99.99% cap' => [$ob->$list()->percentileCap(0.9999), '', ''],
            '-' => ['-', '-', '-'],
            ];
        }

        return $this->render('AppBundle::order-book.html.twig', [
            'stats' => $stats,
        ]);
    }

    /**
     * Show information about trading pairs and automatically execute.
     *
     * @Route("trade/trade", name="trade")
     *
     * @param Request $request Symfony request
     *
     * @return Response
     */
    public function tradeIndex(Request $request)
    {
        $tp = $this->get('bitstamp.trade_pairs');

        $timeFormat = 'Y-m-d H:i:s';

        $stats = [
            '-Bids-' => '',
            'bid/buy USD Base Volume' => $tp->volumeUSDBid()->getAmount(),
            'bid/buy BTC Volume' => $tp->bidBTCVolume()->getAmount(),
            'bid/buy USD Price' => $tp->bidPrice()->getAmount(),
            'bid/buy USD Volume post fees (what USD must we spend to play?)' => $tp->volumeUSDBidPostFees()->getAmount(),
            'bid/buy BTC Volume * USD Price' => $tp->bidBTCVolume()->getAmount() * $tp->bidPrice()->getAmount(),
            'bid/buy BTC Volume * USD Price as USD' => $tp->bidBTCVolume()->getAmount() * $tp->bidPrice()->getAmount() / (10 ** $tp::BTC_PRECISION),
            '-Asks-' => '',
            'ask/sell USD Base Volume' => $tp->volumeUSDAsk()->getAmount(),
            'ask/sell BTC Volume' => $tp->askBTCVolume()->getAmount(),
            'ask/sell USD Price' => $tp->askPrice()->getAmount(),
            'ask/sell BTC Volume * USD Price' => $tp->askBTCVolume()->getAmount() * $tp->askPrice()->getAmount(),
            'ask/sell BTC Volume * USD Price as USD' => $tp->askBTCVolume()->getAmount() * $tp->askPrice()->getAmount() / (10 ** $tp::BTC_PRECISION),
            'ask/sell USD Volume post fees (what USD can we keep from sale?)' => $tp->volumeUSDAskPostFees()->getAmount(),
            '-Diff-' => '',
            'BTC Profit (satoshis)' => $tp->profitBTC()->getAmount(),
            'BTC Profit (BTC)' => $tp->profitBTC()->getAmount() / (10 ** $tp::BTC_PRECISION),
            'BTC Profit USD value (midpoint) as USD cents' => $tp->profitBTC()->getAmount() * $tp->midprice()->getAmount() / (10 ** $tp::BTC_PRECISION),
            'USD Profit (USD cents)' => $tp->profitUSD()->getAmount(),
            'Is profitable' => $tp->isProfitable() ? 'Yes' : 'No',
            'Has dupes' => $tp->hasDupes() ? 'Yes' : 'No',
            'Is valid trade' => $tp->isValid() ? 'Yes' : 'No',
            '-Dupes-' => '',
            'Dupe bid range' => $tp->bidPrice()->getAmount() * $tp->dupes->rangeMultiplier(),
            'Dupe bids' => var_export($tp->dupes->bids($tp->bidPrice()), true),
            'Dupe ask range' => $tp->askPrice()->getAmount() * $tp->dupes->rangeMultiplier(),
            'Dupe asks' => var_export($tp->dupes->asks($tp->askPrice()), true),
            '-Facts-' => '',
            'Fees bids multiplier' => $tp->fees->bidsMultiplier(),
            'Fees asks multiplier' => $tp->fees->asksMultiplier(),
        ];

        return $this->render('AppBundle::index.html.twig', [
            'stats' => $stats,
        ]);
    }

}
