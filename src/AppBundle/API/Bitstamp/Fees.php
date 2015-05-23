<?php

namespace AppBundle\API\Bitstamp;

use Money\Money;

class Fees
{
    public function __construct(PrivateAPI\Balance $balance)
    {
      $this->balance = $balance;
    }

    protected function raw() {
      return (float) $this->balance->data()['fee'];
    }

    /**
     * Fees as a percentage.
     * @return float
     *   Fees as a percentage. E.g. 1% = 1
     */
    public function percent()
    {
      // Bitstamp reports fees natively in this format.
      return $this->raw();
    }

    /**
     * Fees as a number that can be used as a multiplier.
     *
     * @return float
     *   Fees as a multiplier. E.g. 1% = 0.01
     */
    public function multiplier()
    {
        return $this->percent() / 100;
    }

    /**
     * USD fees without rounding, as a float.
     *
     * This method is not public because external classes should *always* deal
     * with the fees the vendor will charge. It is very useful for internal
     * calculations though because we can't guarantee that vendors will apply
     * rounding to fee calculations in the same way that Money handles rounding.
     *
     * @return float
     *   The fees before rounding.
     */
    protected function absoluteFeeUSDNoRounding(Money $USD)
    {
      return $USD->getAmount() * $this->multiplier();
    }

    /**
     * USD fees as an absolute value in USD cents, for a given USD volume.
     *
     * @param Money $USD
     *   Some USD money to calculate a fee for.
     *
     * @return Money
     *   The fee, as Money.
     */
    public function absoluteFeeUSD(Money $USD) {
        if ($USD->getAmount() < 0) {
          throw new \Exception('Cannot calculate fees for negative amounts');
        }

        // We kindly ask our users to take note on Bitstamp's policy regarding fee
        // calculation. As our fees are calculated to two decimal places, all fees
        // which might exceed this limitation are rounded up. The rounding up is
        // executed in such a way, that the second decimal digit is always one
        // digit value higher than it was before the rounding up. For example; a
        // fee of 0.111 will be charged as 0.12.
        // @see https://www.bitstamp.net/fee_schedule/
        return Money::USD((int) ceil($this->absoluteFeeUSDNoRounding($USD)));
    }

    /**
     * Calculate the max USD value that incurs the same fees as a given value.
     *
     * E.g.:
     *   - Fee multiplier = 0.0025
     *   - USD cents = 500
     *
     * Fee with no rounding = 500 * 0.0025 = 1.25 cents
     * Fee with ceiling based rounding = ciel(500 * 0.0025) = 2 cents
     *
     * Maximum USD cents with the same fee as 500 is the volume of USD that has
     * exactly the same fee as 500 calculated with and without rounding, or 2 in
     * this example.
     *
     * - X = Base USD
     * - Y = Max USD
     * - F = Fee multiplier
     *
     * X * ciel(X * F) = Y * F
     * => Y = X * (ceil(X * F) / F)
     * => Y = X * (roundedAbsoluteFee / rawAbsoluteFee)
     *
     * If Y itself is not an integer, we must floor it or incur the next band of
     * fees for that extra fraction of a cent.
     *
     * @param Money $USD
     *   Some Money to scale to the maximum with the same fee.
     */
    public function isofeeMaxUSD(Money $USD) {
      $Y = $USD->getAmount() * ($this->absoluteFeeUSD($USD)->getAmount() / $this->absoluteFeeUSDNoRounding($USD));
      // Yes, int casting floors things anyway, but this behaviour is clearer.
      $Y = (int) floor($Y);
      return Money::USD($Y);
    }
}
