<?php

namespace AppBundle\API\Bitstamp;

use Money\Money;
use AppBundle\MoneyStrings;

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

    public function __construct(PrivateAPI\OpenOrders $openOrders) {
      $this->openOrders = $openOrders;
    }

    protected function validateType($type) {
        $types = [self::TYPE_BUY, self::TYPE_SELL];
        if (!in_array($type, $types)) {
          throw new \Exception('Unknown order type: ' . $type);
        }
    }

    protected function findOpenOrdersWithinPriceRange(Money $price, Money $range, $type) {
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
            $dupes[] = $orderPrice;
          }
        }

        return $matches;
    }

    protected function findDupes(Money $price, $type)
    {
        $this->validateType($type);

        $dupeRange = $price->multiply($this->rangeMultiplier());

        return $this->findOpenOrdersWithinPriceRange($price, $dupeRange, $type);
    }

    public function bids(Money $price) {
      return $this->findDupes($price, self::TYPE_BUY);
    }

    public function asks(Money $price) {
      return $this->findDupes($price, self::TYPE_SELL);
    }

    public function rangeMultiplier() {
      return self::DUPE_RANGE_MULTIPLIER;
    }
}
