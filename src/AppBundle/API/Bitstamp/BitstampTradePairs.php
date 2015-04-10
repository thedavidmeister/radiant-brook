<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\Balance;

class BitstampTradePairs
{

  protected $_fee;

  protected $_volume;

  // As of May 15, 2014 the minimum allowable trade will be USD $5.
  const MIN_VOLUME_USD = 5;

  // Bitstamp limits the fidelity of BTC trades.
  const BTC_FIDELITY = 8;

  // The percentile of cap/volume we'd like to trade to.
  const PERCENTILE = 0.05;

  public function __construct()
  {
    $this->orderBook = new OrderBook();
    $this->balance = new Balance();
  }

  public function fee()
  {
    if (!isset($this->_fee)) {
      $this->_fee = $this->balance->execute()['fee'];
    }
    return $this->_fee;
  }

  public function volumeUSD()
  {
    if (!isset($this->_volume)) {
      // Start with the minimum volume allowable.
      $volume = $this::MIN_VOLUME_USD;

      // Get the fee percentage.
      $fee = $this->fee();

      // Calculate the absolute fee at the min USD volume.
      $fee_absolute = $volume * $fee;

      // We kindly ask our users to take note on Bitstamp's policy regarding fee
      // calculation. As our fees are calculated to two decimal places, all fees
      // which might exceed this limitation are rounded up. The rounding up is
      // executed in such a way, that the second decimal digit is always one
      // digit value higher than it was before the rounding up. For example; a
      // fee of 0.111 will be charged as 0.12.
      // @see https://www.bitstamp.net/fee_schedule/
      $fee_absolute_rounded = ceil($fee_absolute * 100) / 100;

      // We can bump our volume up to the next integer fee value without
      // incurring extra cost to achieve improved effective prices.
      $volume_adjusted = ($fee_absolute_rounded / $fee_absolute) * $volume;

      $this->_volume = $volume_adjusted;
    }

    return $this->_volume;
  }

  // We can't use this inside volumeUSD because we'd have circular deps.
  public function feeAbsolute()
  {
    return ceil($this->volumeUSD() * $this->fee() * 100) / 100;
  }

  /**
   * BIDS
   */

  public function bidPrice()
  {
    // For bids, we use the cap percentile as it's harder for other users to
    // manipulate and we want 1 - PERCENTILE as bids are decending.
    return $this->orderBook->bids()->percentCap(1 - $this::PERCENTILE)[0];
  }

  // @todo test this lots.
  public function bidBTCVolume()
  {
    $rounded = round($this->volumeUSD() / $this->bidPrice(), $this::BTC_FIDELITY);
    // Its very important that when we lodge our bid with Bitstamp, the volume
    // times the price does not exceed the USD volume cap for the current fee,
    // or we pay the fee for the next bracket for no price advantage.
    // @todo - handle this better using native PHP rounding.
    if (($rounded * $this->bidPrice()) > $this->volumeUSD()) {
      $rounded -= 10 ** -($this::BTC_FIDELITY - 1);
    }
    return $rounded;
  }

  public function bidPriceEffective()
  {
    return ($this->bidPrice() * $this->bidBTCVolume() + $this->feeAbsolute()) / $this->bidBTCVolume();
  }

  /**
   * ASKS
   */

  public function askPrice()
  {
    // For asks, we use the BTC volume percentile as it's harder for other users
    // to manipulate. Asks are sorted ascending so we can use $pc directly.
    return $this->orderBook->asks()->percentile($this::PERCENTILE)[0];
  }

  public function askBTCVolume()
  {
    $rounded = round($this->volumeUSD() / $this->askPrice(), $this::BTC_FIDELITY);
    // @see bidBTCVolume()
    // @todo - handle this better using native PHP rounding.
    if (($rounded * $this->askPrice()) > $this->volumeUSD()) {
      $rounded -= 10 ** -($this::BTC_FIDELITY - 1);
    }
    return $rounded;
  }

  public function askPriceEffective()
  {
    return ($this->askPrice() * $this->askBTCVolume() - $this->feeAbsolute()) / $this->askBTCVolume();
  }

  /**
   * DIFF
   */

  public function profit()
  {
    // @todo - figure out how fees fit into this.
    return $this->bidBTCVolume() - $this->askBTCVolume();
  }

  public function percentileIsProfitable()
  {
    $b = '<br />';
    $things = [
      $this->volumeUSD(),
      '<b>bids:</b>',
      $this->bidPrice(),
      $this->bidBTCVolume(),
      $this->bidBTCVolume() * $this->bidPrice(),
      $this->bidPriceEffective(),
      '<b>asks:</b>',
      $this->askPrice(),
      $this->askBTCVolume(),
      $this->askPriceEffective(),
      '<b>diff:</b>',
      $this->profit(),
    ];
    print implode($b, $things);
  }
}
