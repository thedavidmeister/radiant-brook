<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\Balance;

class BitstampTradePairs
{

  protected $_fee;

  protected $_volume;

  // @todo add a way to calculate volume.
  // As of May 15, 2014 the minimum allowable trade will be USD $5.
  const MIN_VOLUME_USD = 5;

  // Bitstamp limits the fidelity of BTC trades.
  const BTC_FIDELITY = 8;

  public function __construct($percentile)
  {
    $this->orderBook = new OrderBook();
    $this->balance = new Balance();
    $this->percentile = $percentile;
  }

  public function fee()
  {
    if (!isset($this->_fee)) {
      $this->_fee = $this->balance->execute()['fee'];
    }
    return $this->_fee;
  }

  public function bidPrice()
  {
    // For bids, we use the cap percentile as it's harder for other users to
    // manipulate and we want 1 - $pc as bids are decending, not ascending.
    return $this->orderBook->bids()->percentCap(1 - $this->percentile)[0];
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
      // incurring extra cost while achieving improved effective prices.
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

  public function percentileIsProfitable()
  {
    $b = '<br />';
    $things = [
      $this->bidPrice(),
      $this->volumeUSD(),
      $this->bidBTCVolume(),
      $this->bidBTCVolume() * $this->bidPrice(),
      $this->bidPriceEffective(),
    ];
    print implode($b, $things);


    // For bids, we use the cap percentile as it's harder for other users to
    // manipulate and we want 1 - $pc as bids are decending, not ascending.
    // $bids_amount = $this->orderBook->bids()->percentCap(1 - $pc)[0];
    // print_r('bid raw: ' . $bids_amount . '<br />');
    // print 'bid BTC volume: ' . $this->bidBTCVolume($bids_amount) . '<br />';
    // print $this->bidBTCVolume($bids_amount) * $bids_amount . '<br />';
    // $fee = $this->bidFee($bids_amount);
    // print_r('bid fees: ' . $fee . '<br />');
    // $post_fees = ($this->bidBTCVolume($bids_amount) * $bids_amount + $fee) / $this->bidBTCVolume($bids_amount);
    // print 'bid price post fees: ' . $post_fees . '<br />';

    // // For asks, we use the BTC volume percentile as it's harder for other users
    // // to manipulate. Asks are sorted ascending so we can use $pc directly.
    // $asks_amount = $this->orderBook->asks()->percentile($pc)[0];
    // print 'asks amount: ' . $asks_amount;

  }
}
