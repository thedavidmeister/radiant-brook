<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use AppBundle\Ensure;
use AppBundle\Cast;
use AppBundle\MoneyConstants;
use Money\Money;

/**
 * AppBundle\API\Bitstamp\TradePairs\TradeProposal.
 */
class TradeProposal
{
    protected $bidUSDPrice;

    protected $askUSDPrice;

    const MIN_USD_VOLUME_SECRET = 'BITSTAMP_MIN_USD_VOLUME';

    const MIN_USD_PROFIT_SECRET = 'BITSTAMP_MIN_USD_PROFIT';

    const MIN_BTC_PROFIT_SECRET = 'BITSTAMP_MIN_BTC_PROFIT';

    const STATE_VALID = 0;

    const STATE_VALID_REASON = 'Valid trade pair.';

    const STATE_INVALID = 1;

    const STATE_PANIC = 2;

    /**
     * DI Constructor.
     * @param array $prices
     * @param Fees  $fees
     */
    public function __construct(
        array $prices,
        Fees $fees
    )
    {
        foreach (['bidUSDPrice', 'askUSDPrice'] as $price) {
            $this->{$price} = $prices[$price];
            Ensure::isInstanceOf($this->{$price}, 'Money\Money');
        }

        $this->fees = $fees;

        $this->secrets = new Secrets();
    }

    /**
     * STATE
     */

    /**
     * Set this TradeProposal to valid.
     *
     * Valid TradeProposals should be executed.
     *
     * There is no need to pass a reason to validate because there is only one
     * possible reason that something is valid.
     */
    public function validate()
    {
        $this->setState(self::STATE_VALID, self::STATE_VALID_REASON);
    }

    /**
     * Set this TradeProposal to invalid.
     *
     * Invalid TradeProposals should not be executed.
     *
     * @param string $reason
     *   The reason this TradeProposal was invalidated.
     */
    public function invalidate($reason)
    {
        $this->setState(self::STATE_INVALID, $reason);
    }

    /**
     * Set this TradeProposal to panic.
     *
     * Panic TradeProposals should not be executed AND no further attempts to
     * execute any Proposals should be attempted.
     *
     * @param string $reason
     *   The reason for this TradeProposal to panic.
     */
    public function panic($reason)
    {
        $this->setState(self::STATE_PANIC, $reason);
    }

    /**
     * Get the current state of the TradeProposal as read-only.
     *
     * Anything other than a 0 is a fail. Higher numbers indicate more extreme
     * failure.
     *
     * @return int
     *   The current state of the TradeProposal.
     */
    public function state()
    {
        if (isset($this->state)) {
            return $this->state;
        } else {
            throw new \Exception('No state has been set for this TradeProposal, it has not been validated correctly.');
        }
    }
    protected $state;

    /**
     * Get the reason for the current state as read-only.
     *
     * @return string
     *   The reason for the current state.
     */
    public function reason()
    {
        if(isset($this->stateReason)) {
            return $this->stateReason;
        } else {
            throw new \Exception('No state reason has been set for this TradeProposal, it has not been validated correctly.');
        }
    }
    protected $stateReason;

    /**
     * Sets the current state.
     *
     * Requires a non-empty reason. States can only be increased in severity as
     * a previous, more severe failure state takes preference over the current,
     * less (or equally) severe state.
     *
     * @param int $state
     *   The new state to attempt.
     *
     * @param string $reason
     *   The reason for the new state.
     */
    protected function setState($state, $reason)
    {
        // We need a reason to consider a state change.
        Ensure::notEmpty($reason);
        Ensure::isString($reason);

        // States can only increase over time (get worse).
        Ensure::isInt($state);

        if ($state > $this->state || !isset($this->state)) {
            $this->state = $state;
            $this->stateReason = $reason;
        }
    }

    /**
     * BIDS
     */

    /**
     * Gets $this->bidUSDPrice.
     *
     * @return Money::USD
     */
    public function bidUSDPrice()
    {
        return $this->bidUSDPrice;
    }

    /**
     * The base USD volume from config pre-isofee scaling.
     *
     * @return Money::USD
     */
    public function bidUSDVolumeBase()
    {
        $minUSDVolumeAmount = Cast::toInt($this->secrets->get(self::MIN_USD_VOLUME_SECRET));

        return Money::USD((int) $minUSDVolumeAmount);
    }

    /**
     * The USD bid volume pre-fees.
     *
     * We can simply scale the minimum USD volume allowable using the fee
     * structure as a limit.
     *
     * @return Money::USD
     */
    public function bidUSDVolume()
    {
        return $this->fees->isofeeMaxUSD($this->bidUSDVolumeBase());
    }

    /**
     * The USD bid volume including fees.
     *
     * We can simply add the fees for this USD volume to the USD volume.
     *
     * @return Money::USD
     */
    public function bidUSDVolumePlusFees()
    {
        return $this->bidUSDVolume()->add($this->fees->absoluteFeeUSD($this->bidUSDVolume()));
    }

    /**
     * The bid BTC volume of the suggested pair.
     *
     * The volume of BTC is simply the amount of USD we have to spend divided by
     * the amount we're willing to spend per Satoshi.
     *
     * @return Money::BTC
     */
    public function bidBTCVolume()
    {
        // Its very important that when we lodge our bid with Bitstamp, the
        // BTC volume times the USD price does not exceed the maximum USD volume
        // on the isofee. For this reason, we floor any fractions of satoshis
        // that come out of this equation to avoid any risk of being one satoshi
        // over the limit from Bitstamp's perspective.
        //
        // For this reason we do NOT use something like MoneyStrings.
        $satoshis = (int) floor(($this->bidUSDVolume()->getAmount() / $this->bidUSDPrice()->getAmount()) * (10 ** MoneyConstants::BTC_PRECISION));

        // This must never happen.
        if ($satoshis * $this->bidUSDPrice()->getAmount() / (10 ** MoneyConstants::BTC_PRECISION) > $this->bidUSDVolume()->getAmount()) {
            // @codeCoverageIgnoreStart
            // This can only happen if the code in this function is broken. ie.
            // We cannot test it.
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->bidUSDPrice()->getAmount() . ' per BTC which exceeds allowed volume USD ' . $this->bidUSDVolume()->getAmount());
            // @codeCoverageIgnoreEnd
        }

        return Money::BTC($satoshis);
    }

    /**
     * ASKS
     */

    /**
     * Gets $this->askUSDPrice.
     *
     * @return Money::USD
     */
    public function askUSDPrice()
    {
        return $this->askUSDPrice;
    }

    /**
     * Returns the USD volume required to cover the bid USD + fees.
     *
     * The volume USD that we get to keep K is:
     *   - X = USD value of BTC sold
     *   - Fa = Fee asks multiplier
     *   - K = X * Fa
     *
     * If we want to keep enough to cover our total bid cost B + profit P then:
     *   - K = B + P
     *
     * Therefore:
     *   - B + P = X * Fa
     *   - X = (B + P) / Fa
     *
     * @return Money::USD
     */
    public function askUSDVolumeCoverFees()
    {
        $x = ($this->bidUSDVolumePlusFees()->getAmount() + $this->secrets->get(self::MIN_USD_PROFIT_SECRET)) / $this->fees->asksMultiplier();

        // We have to ceil() $x or risk losing our USD profit to fees.
        return Money::USD((int) ceil($x));
    }

    /**
     * How much USD can we keep from our sale, post fees?
     *
     * @return Money::USD
     */
    public function askUSDVolumePostFees()
    {
        return Money::USD((int) floor($this->askUSDVolumeCoverFees()->getAmount() * $this->fees->asksMultiplier()));
    }

    /**
     * The asking volume of BTC in the suggested pair.
     *
     * BTC volume is simply the amount of USD we need to sell divided by the
     * USD price per BTC.
     *
     * @return Money::BTC
     */
    public function askBTCVolume()
    {
        // We have to ceiling our satoshis to guarantee that we meet our minimum
        // ask USD volume, or we risk fees killing our profits.
        $satoshis = (int) ceil($this->askUSDVolumeCoverFees()->getAmount() / $this->askUSDPrice()->getAmount() * 10 ** MoneyConstants::BTC_PRECISION);

        // This must never happen.
        if ($satoshis * $this->askUSDPrice()->getAmount() / (10 ** MoneyConstants::BTC_PRECISION) < $this->askUSDVolumeCoverFees()->getAmount()) {
            // @codeCoverageIgnoreStart
            // This can only happen if the code in this function is broken. ie.
            // We cannot test it.
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->askUSDPrice()->getAmount() . ' per BTC which does not meet required volume USD ' . $this->askUSDVolumeCoverFees()->getAmount());
            // @codeCoverageIgnoreEnd
        }

        return Money::BTC($satoshis);
    }

    /**
     * PROFIT
     */

    /**
     * Returns the USD profit of the suggested pair.
     *
     * @return Money::USD
     */
    public function profitUSD()
    {
        return Money::USD((int) floor($this->askUSDVolumePostFees()->getAmount() - $this->bidUSDVolumePlusFees()->getAmount()));
    }

    /**
     * Returns the BTC profit of the suggested pair.
     *
     * @return Money::BTC
     */
    public function profitBTC()
    {
        return Money::BTC((int) floor($this->bidBTCVolume()->getAmount() - $this->askBTCVolume()->getAmount()));
    }

    /**
     * Returns the minimum acceptable USD profit for a valid pair.
     *
     * @return Money::USD
     */
    public function minProfitUSD()
    {
        $minProfitUSD = Cast::toInt($this->secrets->get(self::MIN_USD_PROFIT_SECRET));

        return Money::USD($minProfitUSD);
    }

    /**
     * Returns the minimum acceptable BTC profit for a valid pair.
     *
     * @return Money::BTC
     */
    public function minProfitBTC()
    {
        $minProfitBTC = Cast::toInt($this->secrets->get(self::MIN_BTC_PROFIT_SECRET));

        return Money::BTC($minProfitBTC);
    }

    /**
     * Returns true if this trade proposal meets minimum profit requirements.
     *
     * @return boolean
     */
    public function isProfitable()
    {
        return $this->profitUSD()->getAmount() >= $this->minProfitUSD()->getAmount()
            && $this->profitBTC()->getAmount() >= $this->minProfitBTC()->getAmount();
    }
}
