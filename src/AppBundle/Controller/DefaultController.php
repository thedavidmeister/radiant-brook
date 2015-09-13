<?php

namespace AppBundle\Controller;

use AppBundle\API\Bitstamp\TradePairs\TradeProposal;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for AppBundle.
 */
class DefaultController extends Controller
{
    /**
     * Just a blank index page.
     *
     * @Route("/", name="index")
     *
     * @return \Symfony\Component\HttpFoundation\Response
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function orderBookAction()
    {
        $ob = $this->get('bitstamp.order_book');

        $stats = array();
        foreach (['bids', 'asks'] as $list) {
            $merge = [
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
                $list . ' -end-' => ['-', '-', '-'],
            ];
            $stats += $merge;
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
     * We have to ignore code coverage because all this requires an API key to
     * function.
     *
     * @codeCoverageIgnore
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tradeAction()
    {
        $tp = $this->get('bitstamp.trade_pairs');

        $stats = [
            '-Facts-' => '',
            'Fees bids multiplier' => $tp->fees()->bidsMultiplier(),
            'Fees asks multiplier' => $tp->fees()->asksMultiplier(),
            'Is trading' => $tp->isTrading(),
        ];

        $report = $tp->report();
        $statsArrayFromProposal = function(TradeProposal $proposal, $prefix = '') {
            $stats = [];
            $methods = [
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
            ];
            foreach ($methods as $method) {
                $stats[$method] = $proposal->{$method}()->getAmount();
            }

            $stats['isProfitable'] = $proposal->isProfitable() ? 'Yes' : 'No';
            $stats['isValid'] = $proposal->isValid();
            $stats['isFinal'] = $proposal->isFinal();
            $stats['isCompulsory'] = $proposal->isCompulsory();
            $stats['reasons'] = json_encode($proposal->reasons());

            $name = md5(serialize($stats));

            // Add prefixes.
            $return = [$name => ''];
            foreach ($stats as $key => $value) {
                $label = implode(' ', array_filter([$prefix, $name, $key]));
                $return[$label] = $value;
            }

            return $return;
        };

        $stats['-Actionable proposal-'] = '';
        $actionable = $tp->reduceReportToActionableTradeProposal($report);
        if (isset($actionable)) {
            $stats += $statsArrayFromProposal($actionable, 'actionable');
        }

        $stats['-Trade proposals-'] = '';
        foreach ($report as $item) {
            $stats += $statsArrayFromProposal($item, 'report');
        }

        return $this->render('AppBundle::index.html.twig', [
            'stats' => $stats,
        ]);
    }

}
