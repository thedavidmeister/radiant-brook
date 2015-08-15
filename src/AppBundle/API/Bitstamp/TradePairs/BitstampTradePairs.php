<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use Money\Money;
use AppBundle\API\Bitstamp\TradePairs\PriceProposer;

use function Functional\first;

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

        foreach ($this->proposer as $proposition) {
            $tradeProposal = new TradeProposal($proposition, $this->fees);
            $this->validateTradeProposition($tradeProposal);
        }
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
            $report[] = ['proposition' => $tradeProposal] + $this->validateTradeProposition($tradeProposal);
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
     * @return array
     *   - state
     *   - reason
     */
    public function validateTradeProposal(TradeProposal $tradeProposal)
    {
        // This proposition is not profitable, but others may be.
        if (!$tradeProposal->isProfitable()) {
            $tradeProposal->invalidate('Not a profitable trade proposition.');
        }

        // If we found dupes, we cannot continue trading, panic!
        if ($this->dupes->tradeProposalHasDupes($tradeProposal)) {
            $tradeProposal->panic('Duplicate trade pairs found.');
        }

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
