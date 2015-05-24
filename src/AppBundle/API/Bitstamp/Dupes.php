<?php

namespace AppBundle\API\Bitstamp;

use Money\Money;
use AppBundle\MoneyStrings;

/**
 * Search for dupes in OpenOrders against a given ask/bid USD price.
 */
class Dupes
{
    // Multiplier on a bid/ask price to consider it a dupe with existing orders.
    const DUPE_RANGE_MULTIPLIER = 0.01;

    // Bitstamp representation of a "buy" order type.
    const TYPE_BUY = 0;

    // Bitstamp representation of a "sell" order type.
    const TYPE_SELL = 1;

    // The key for price of an open order.
    const KEY_PRICE = 'price';

    // The key for type of an open order.
    const KEY_TYPE = 'type';

    /**
     * DI constructor.
     *
     * @param PrivateAPI\OpenOrders $openOrders
     */
    public function __construct(PrivateAPI\OpenOrders $openOrders)
    {
        $this->openOrders = $openOrders;
    }

    protected function validateType($type)
    {
        $types = [self::TYPE_BUY, self::TYPE_SELL];
        if (!in_array($type, $types)) {
            throw new \Exception('Unknown order type: ' . $type);
        }
    }

    /**
     * Looks up dupes within a range of prices.
     *
     * @param Money $price
     *   The USD Money price to look for dupes of.
     *
     * @param Money $range
     *   The absolute USD Money price range to +/- against $price to set the
     *   dupes range.
     *
     * @param int $type
     *   Bids or Asks type, as per Bitstamp API in open_orders.
     *
     * @return array
     *   The dupes found array.
     */
    protected function findOpenOrdersWithinPriceRange(Money $price, Money $range, $type)
    {
        $this->validateType($type);

        // Define upper and lower bounds.
        $upperPriceBound = $price->add($range);
        $lowerPriceBound = $price->subtract($range);

        $matches = [];
        foreach ($this->openOrders->data() as $order) {
            // Ignore any orders of the wrong type.
            if ($order[self::KEY_TYPE] != $type) {
                continue;
            }

            // Check upper and lower bounds.
            $orderPrice = MoneyStrings::stringToUSD($order[self::KEY_PRICE]);
            if ($orderPrice->greaterThan($lowerPriceBound) && $orderPrice->lessThan($upperPriceBound)) {
                $matches[] = $orderPrice;
            }
        }

        return $matches;
    }

    /**
     * Hands off bids() or asks() to the dupe finding backend.
     *
     * @param Money $price
     *   The price to check.
     *
     * @param int $type
     *   Bids or Asks type, as per Bitstamp API in open_orders.
     *
     * @return array
     *   The dupes found array.
     */
    protected function findDupes(Money $price, $type)
    {
        $this->validateType($type);

        $dupeRange = $price->multiply($this->rangeMultiplier());

        return $this->findOpenOrdersWithinPriceRange($price, $dupeRange, $type);
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
        return self::DUPE_RANGE_MULTIPLIER;
    }
}
