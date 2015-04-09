<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\Balance;

class BitstampTradePairs
{

  protected $_fee;

  // @todo add a way to calculate volume.
  const VOLUMEUSD = 5;

  public function __construct() {
    $this->orderBook = new OrderBook();
    $this->balance = new Balance();
  }

  public function bidBTCVolume($bid_price) {
    $rounded = round($this::VOLUMEUSD / $bid_price, 8);
    // @todo - handle this better using native PHP rounding.
    if (($rounded * $bid_price) < $this::VOLUMEUSD) {
      $rounded += 0.0000001;
    }
    return $rounded;
  }

  public function fee() {
    if (!isset($this->_fee)) {
      $this->_fee = $this->balance->execute()['fee'];
    }
    return $this->_fee;
  }

  public function bidFee($bid_price) {
    $fee = $this->bidBTCVolume($bid_price) * $bid_price * $this->fee();
    // We kindly ask our users to take note on Bitstamp's policy regarding fee
    // calculation. As our fees are calculated to two decimal places, all fees
    // which might exceed this limitation are rounded up. The rounding up is
    // executed in such a way, that the second decimal digit is always one digit
    // value higher than it was before the rounding up. For example; a fee of
    // 0.111 will be charged as 0.12.
    // @see https://www.bitstamp.net/fee_schedule/
    $rounded = ceil($fee * 100) / 100;
    return $rounded;
  }

  public function percentileIsProfitable($pc) {
    // For bids, we use the cap percentile as it's harder for other users to
    // manipulate and we want 1 - $pc as bids are decending, not ascending.
    $bids_amount = $this->orderBook->bids()->percentCap(1 - $pc)[0];
    print_r('bid raw: ' . $bids_amount . '<br />');
    print 'bid BTC volume: ' . $this->bidBTCVolume($bids_amount) . '<br />';
    print $this->bidBTCVolume($bids_amount) * $bids_amount . '<br />';
    $fee = $this->bidFee($bids_amount);
    print_r('bid fees: ' . $fee . '<br />');
    $post_fees = ($this->bidBTCVolume($bids_amount) * $bids_amount + $fee) / $this->bidBTCVolume($bids_amount);
    print 'bid price post fees: ' . $post_fees . '<br />';

    // For asks, we use the BTC volume percentile as it's harder for other users
    // to manipulate. Asks are sorted ascending so we can use $pc directly.
    $asks_amount = $this->orderBook->asks()->percentile($pc)[0];
    print 'asks amount: ' . $asks_amount;

  }
}
