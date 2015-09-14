<?php

namespace AppBundle\API\Bitstamp\TradePairs;

use AppBundle\MoneyStringsUtil;
use AppBundle\Secrets;
use Money\Money;
use Respect\Validation\Validator as v;

/**
 * Search for dupes in OpenOrders against a given ask/bid USD price.
 */
class Dupes
{
    // Bitstamp representation of a "buy" order type.
    const TYPE_BUY = 0;

    // Bitstamp representation of a "sell" order type.
    const TYPE_SELL = 1;

    // The key for price of an open order.
    const KEY_PRICE = 'price';

    // The key for type of an open order.
    const KEY_TYPE = 'type';

    // The secret name for the range multiplier
    const DUPES_RANGE_MULTIPLIER_SECRET = 'DUPES_RANGE_MULTIPLIER';

    protected $openOrders;

    protected $secrets;

    /**
     * DI constructor.
     *
     * @param \AppBundle\API\Bitstamp\PrivateAPI\OpenOrders $openOrders
     * @param \AppBundle\Secrets                            $secrets
     */
    public function __construct(
        \AppBundle\API\Bitstamp\PrivateAPI\OpenOrders $openOrders,
        Secrets $secrets
    )
    {
        $this->openOrders = $openOrders;
        $this->secrets = $secrets;
    }

    /**
     * Looks up dupes within a range of prices.
     *
     * @param Money $price
     *   The USD Money price to look for dupes of.
     *
     *
     * @param int $type
     *   Bids or Asks type, as per Bitstamp API in open_orders.
     *
     * @return array
     *   The dupes found array.
     */
    protected function findDupes(Money $price, $type)
    {
        $matches = [];
        foreach ($this->openOrders->data() as $order) {
            // Ignore any orders of the wrong type.
            if ($order[self::KEY_TYPE] != $type) {
                continue;
            }

            // Check upper and lower bounds.
            $orderPrice = MoneyStringsUtil::stringToUSD($order[self::KEY_PRICE]);
            if ($orderPrice->greaterThan($this->bounds($price)['lower']) && $orderPrice->lessThan($this->bounds($price)['upper'])) {
                $matches[] = $orderPrice;
            }
        }

        return $matches;
    }

    /**
     * Calculates the dupes range, upper and lower bounds for a given price.
     *
     * @param Money $price
     *   Price to calculate the bounds for.
     *
     * @return array<string,Money>
     *   Array with keys 'range', 'upper', 'lower', values are Money::USD.
     */
    public function bounds(Money $price)
    {
        $range = $price->multiply($this->rangeMultiplier());
        // Define upper and lower bounds.
        $upperPriceBound = $price->add($range);
        $lowerPriceBound = $price->subtract($range);

        $return = ['range' => $range, 'upper' => $upperPriceBound, 'lower' => $lowerPriceBound];

        v::each(v::instance('Money\Money'))->check($return);

        return $return;
    }

    /**
     * Returns bids dupes based on a given bids price.
     *
     * @param Money $price
     *   The bids price to check dupes against.
     *
     * @return array
     *   An array of bid price dupes found.
     */
    public function bids(Money $price)
    {
        return $this->findDupes($price, self::TYPE_BUY);
    }

    /**
     * Returns asks dupes based on a given asks price.
     *
     * @param Money $price
     *   The asks price to check dupes against.
     *
     * @return array
     *   An array of ask price dupes found.
     */
    public function asks(Money $price)
    {
        return $this->findDupes($price, self::TYPE_SELL);
    }

    /**
     * Returns the dupe range multiplier.
     *
     * @return float
     *   The range multiplier.
     */
    public function rangeMultiplier()
    {
        return (float) $this->secrets->get(self::DUPES_RANGE_MULTIPLIER_SECRET);
    }
}
