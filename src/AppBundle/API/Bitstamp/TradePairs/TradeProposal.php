<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use AppBundle\Ensure;
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
        return Money::USD((int) $this->secrets->get(self::MIN_USD_VOLUME_SECRET));
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
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->bidUSDPrice()->getAmount() . ' per BTC which exceeds allowed volume USD ' . $this->bidUSDVolume()->getAmount());
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
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->askUSDPrice()->getAmount() . ' per BTC which does not meet required volume USD ' . $this->askUSDVolumeCoverFees()->getAmount());
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
        $minProfitUSD = Ensure::isInt($this->secrets->get(self::MIN_USD_PROFIT_SECRET));

        return Money::USD((int) $minProfitUSD);
    }

    /**
     * Returns the minimum acceptable BTC profit for a valid pair.
     *
     * @return Money::BTC
     */
    public function minProfitBTC()
    {
        $minProfitBTC = Ensure::isInt($this->secrets->get(self::MIN_BTC_PROFIT_SECRET));

        return Money::BTC((int) $minProfitBTC);
    }

    public function isProfitable() {
        return $this->profitUSD() >= $this->minProfitUSD() && $this->profitBTC() > $this->minProfitBTC();
    }
}