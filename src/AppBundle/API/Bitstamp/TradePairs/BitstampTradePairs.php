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

    const PROPOSAL_VALID = 'valid';

    const PROPOSAL_INVALID = 'invalid';

    const PROPOSAL_PANIC = 'panic';

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
            switch($this->validateTradeProposition($tradeProposal)) {
                case self::PROPOSAL_VALID:
                    // Do trade.
                    // return, loop cannot continue.
                    return;
                case self::PROPOSAL_INVALID:
                    // Do nothing. Allow loop to continue.
                    break;
                case self::PROPOSAL_PANIC:
                    // Do nothing.
                    // return, loop cannot continue.
                    return;
            }
        }
    }

    public function report()
    {
        $report = [];
        foreach ($this->proposer as $proposition) {
            $tradeProposal = new TradeProposal($proposition, $this->fees);
            $report[] = ['proposition' => $tradeProposal] + $this->validateTradeProposition($tradeProposal);
        }
        return $report;
    }

    public function validateTradeProposition(TradeProposal $tradeProposal) {
        $state = self::PROPOSAL_VALID;
        $reason = 'Valid trade pair.';

        // If we found dupes, we cannot continue trading, panic!
        if ($this->dupes->tradeProposalHasDupes($tradeProposal)) {
            $state = self::PROPOSAL_PANIC;
            $reason = 'Duplicate trade pairs found.';
        }

        // This proposition is not profitable, but others may be.
        if (!$tradeProposal->isProfitable()) {
            $state = self::PROPOSAL_INVALID;
            $reason = 'Not a profitable trade proposition.';
        }

        return ['state' => $state, 'reason' => $reason];
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

    // /**
    //  * Does the pair meet all requirements for execution?
    //  *
    //  * @return bool
    //  */
    // public function ensureValid()
    // {
    //     $errors = [];

    //     // break statements are intentionally left out here to allow multiple
    //     // error messages to be collated.
    //     switch (false) {
    //         case $this->isTrading():
    //             $errors[] = 'Bitstamp trading is disabled at this time.';
    //         case $this->isProfitable():
    //             $errors[] = 'No profitable trade pairs found.';
    //         case !$this->hasDupes():
    //             $errors[] = 'Duplicate trade pairs found';
    //     }

    //     if (!empty($errors)) {
    //         throw new \Exception('Invalid trade pairs: ' . implode(' ', $errors));
    //     }

    //     return true;
    // }
}
