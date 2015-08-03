<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\Secrets;
use Money\Money;

class Volumizer
{
  protected $prices;

    const MIN_USD_VOLUME_SECRET = 'BITSTAMP_MIN_USD_VOLUME';


  public function __construct(
    array $prices,
    Fees $fees
  )
  {
    foreach (['bidUSDPrice', 'askUSDPrice'] as $price) {
      $this->{$price} = isset($prices[$price]) ? $prices[$price] : throw new \Exception('Missing ' . $price);
    }

    $this->fees = $fees;
    $this->secrets = new Secrets();
  }

  public function bidUSDPrice() {
    return $this->bidUSDPrice;
  }

  public function askUSDPrice() {
    return $this->askUSDPrice;
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
        return $this->volumeUSDBid()->add($this->fees->absoluteFeeUSD($this->volumeUSDBid()));
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
        $satoshis = (int) floor(($this->bidUSDVolume()->getAmount() / $this->bidUSDPrice()->getAmount()) * (10 ** self::BTC_PRECISION));

        // This must never happen.
        if ($satoshis * $this->bidUSDPrice()->getAmount() / (10 ** self::BTC_PRECISION) > $this->bidUSDVolume()->getAmount()) {
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->bidUSDPrice()->getAmount() . ' per BTC which exceeds allowed volume USD ' . $this->bidUSDVolume()->getAmount());
        }

        return Money::BTC($satoshis);
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
        $satoshis = (int) ceil($this->askUSDVolumeCoverFees()->getAmount() / $this->askUSDPrice()->getAmount() * 10 ** self::BTC_PRECISION);

        // This must never happen.
        if ($satoshis * $this->askUSDPrice()->getAmount() / (10 ** self::BTC_PRECISION) < $this->askUSDVolumeCoverFees()->getAmount()) {
            throw new \Exception($satoshis . ' satoshis were attempted to be purchased at ' . $this->askUSDPrice()->getAmount() . ' per BTC which does not meet required volume USD ' . $this->askUSDVolumeCoverFees()->getAmount());
        }

        return Money::BTC($satoshis);
    }

  public function get()
  {
    return $this->prices += [
      'bidBTCVolume' => $this->bidBTCVolume(),
      'askBTCVolume' => $this->askBTCVolume(),
    ];
  }
}
