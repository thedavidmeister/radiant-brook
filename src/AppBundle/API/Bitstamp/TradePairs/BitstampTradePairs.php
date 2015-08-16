<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use Money\Money;
use AppBundle\API\Bitstamp\TradePairs\PriceProposer;

/**
 * Suggests and executes profitable trade pairs.
 *
 * The algorithm used for suggesting is:
 *
 * - Get the 5% percentile of bids as a USD price for BTC
 * - Get the minimum USD amount, scaled up to maximum on isofee
 * - Get the volume of BTC purchaseable for chosen USD price & volume
 * - Get the total USD amount, including fees
 *
 * - Get the 5% percentile of asks as a USD price for BTC
 * - Get the USD amount to cover, including bid/ask fees and min USD profit
 * - Get the minimum total BTC volume to sell to cover USD amount, scaled to
 *   minimum isofee
 *
 * - If the USD amount spent in bid can be covered with min USD profit, and the
 *   BTC sold is less than the BTC bought, and there are no dupes, place a pair.
 */
class BitstampTradePairs
{
    const IS_TRADING_SECRET = 'BITSTAMP_IS_TRADING';

    const PERCENTILE_SECRET = 'BITSTAMP_PERCENTILE';

    /**
     * Constructor to store services passed by Symfony.
     *
     * @param Fees          $fees
     *   Bitstamp Fees service.
     *
     * @param Dupes         $dupes
     *   Bitstamp Dupes service.
     *
     * @param BuySell       $buySell
     *   Combined Bitstamp buy/sell service.
     *
     * @param PriceProposer $proposer
     *   Bitstamp proposer service.
     */
    public function __construct(
        Fees $fees,
        Dupes $dupes,
        BuySell $buySell,
        PriceProposer $proposer
    )
    {
        $this->fees = $fees;
        $this->dupes = $dupes;
        $this->buySell = $buySell;
        $this->proposer = $proposer;
        $this->secrets = new Secrets();
    }

    /**
     * Execute the suggested trade pairs with Bitstamp.
     *
     * If $this fails validation, the trade pairs will not be executed and an
     * exception thrown, to protect against unprofitable and duplicate orders.
     */
    public function execute()
    {
        if (!$this->isTrading()) {
            throw new \Exception('Bitstamp trading is disabled at this time.');
        }

        $report = $this->report();

        $proposalToAction = $this->reduceReportToActionableTradeProposal($report);

        if (isset($proposalToAction)) {
            if ($proposalToAction->state() === TradeProposal::STATE_VALID) {
                $this->buySell->execute($proposalToAction);
            } else {
                throw new \Exception('Proposal action is not valid. State: ' . $proposalToAction->state() . ', reason: ' . $proposalToAction->reason());
            }
        } else {
            throw new \Exception('No valid trade proposals at this time.');
        }
    }

    /**
     * Parse an action plan report into a single actionable proposal.
     *
     * @param array $report
     *   A report of TradeProposal objects, as provided by report().
     *
     * @return TradeProposal
     *   A TradeProposal that can be actioned.
     */
    public function reduceReportToActionableTradeProposal(array $report)
    {
        $actionable = null;

        foreach ($report as $tradeProposal) {
            Ensure::isInstanceOf($tradeProposal, 'AppBundle\API\Bitstamp\TradePairs\TradeProposal');

            if ($tradeProposal->state() === TradeProposal::STATE_PANIC) {
                $actionable = $tradeProposal;
                break;
            }

            if ($tradeProposal->state() === TradeProposal::STATE_VALID) {
                $actionable = !isset($actionable) ? $tradeProposal : $actionable;
            }
        }

        return $actionable;
    }

    /**
     * Provides a action plan report for a set of TradeProposals.
     *
     * @return array $report
     *   - proposition
     *   - state
     *   - reason
     */
    public function report()
    {
        $report = [];
        foreach ($this->proposer as $proposition) {
            $tradeProposal = new TradeProposal($proposition, $this->fees);
            $report[] = $this->validateTradeProposal($tradeProposal);
        }

        return $report;
    }

    /**
     * Validates a given TradeProposal.
     *
     * A TradeProposal can either be valid (executable), invalid (unexecutable),
     * or panic (halt all proposals).
     *
     * @param  TradeProposal $tradeProposal
     *
     * @return TradeProposal
     *   A validated TradeProposal
     */
    public function validateTradeProposal(TradeProposal $tradeProposal)
    {
        // This proposition is not profitable, but others may be.
        if (!$tradeProposal->isProfitable()) {
            $tradeProposal->invalidate('Not a profitable trade proposition.');
        }

        // If we found dupes, we cannot continue trading, panic!
        if (!empty($this->dupes->bids($tradeProposal->bidUSDPrice()) + $this->dupes->asks($tradeProposal->askUSDPrice()))) {
            $tradeProposal->panic('Duplicate trade pairs found.');
        }

        // Validate the $tradeProposal so that it has a state.
        $tradeProposal->validate();

        return $tradeProposal;
    }

    /**
     * Is trading currently enabled?
     *
     * The following values for the BITSTAMP_IS_TRADING environment variable is
     * supported as true:
     * - '1'
     * - 'true'
     * - 'yes'
     *
     * @return bool
     *   true if trading is enabled.
     */
    public function isTrading()
    {
        $isTrading = $this->secrets->get(self::IS_TRADING_SECRET);

        return filter_var($isTrading, FILTER_VALIDATE_BOOLEAN);
    }
}
