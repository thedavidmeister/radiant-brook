<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\OrderBook;
use AppBundle\API\Bitstamp\Balance;
use Symfony\Component\Validator\Constraints as Assert;

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

  // The minimum amount of USD profit we need to commit to a pair.
  const MIN_PROFIT_USD = 0.01;

  // Multiplier on a bid/ask price to consider it a dupe with existing orders.
  const DUPE_RANGE_MULTIPLIER = 0.01;

  public function __construct()
  {
    $this->orderBook = new OrderBook();
    $this->balance = new Balance();
    $this->openOrders = new OpenOrders();
    $this->sell = new Sell();
    $this->buy = new Buy();
  }

  public function datetime($service) {
    return $this->{$service}->datetime();
  }

  public function fee()
  {
    if (!isset($this->_fee)) {
      // Bitstamp sends us the fee as a percentage represented as a decimal,
      // e.g. 0.25% is handed to us as 0.25 rather than 0.0025, which will make
      // all subsequent math difficult, so it's worth massaging the value here.
      $this->_fee = $this->balance->execute()['fee'] / 100;
    }
    return $this->_fee;
  }

  public function volumeUSDBid()
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

  public function volumeUSDBidPostFees() {
    return ceil($this->bidBTCVolume() * $this->bidPrice() * (1 + $this->fee()) * 100) / 100;
  }

  /**
   * Returns the USD volume required to cover the bid USD + fees.
   */
  public function volumeUSDAsk() {
    // @todo - Is (1 + $this->fee() * 2) correct?
    return $this->volumeUSDBidPostFees() * (1 + $this->fee() * 2) + $this::MIN_PROFIT_USD;
  }

  // We can't use this inside volumeUSDBid because we'd have circular deps.
  protected function feeAbsolute()
  {
    return ceil($this->volumeUSDBid() * $this->fee() * 100) / 100;
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
    $rounded = round($this->volumeUSDBid() / $this->bidPrice(), $this::BTC_FIDELITY);
    // Its very important that when we lodge our bid with Bitstamp, the volume
    // times the price does not exceed the USD volume cap for the current fee,
    // or we pay the fee for the next bracket for no price advantage.
    if (($rounded * $this->bidPrice()) > $this->volumeUSDBid()) {
      $rounded -= 10 ** -($this::BTC_FIDELITY - 1);
    }
    return $rounded;
  }

  protected function bidPriceEffective()
  {
    return ($this->bidPrice() * $this->bidBTCVolume() + $this->feeAbsolute()) / $this->bidBTCVolume();
  }

  /**
   * ASKS
   */

  public function volumeUSDAskPostFees() {
    return floor($this->askBTCVolume() * $this->askPrice() * (1 - $this->fee()) * 100) / 100;
  }

  public function askPrice()
  {
    // For asks, we use the BTC volume percentile as it's harder for other users
    // to manipulate. Asks are sorted ascending so we can use $pc directly.
    return $this->orderBook->asks()->percentile($this::PERCENTILE)[0];
  }

  public function askBTCVolume()
  {
    $rounded = round($this->volumeUSDAsk() / $this->askPrice(), $this::BTC_FIDELITY);
    // @see bidBTCVolume()
    if (($rounded * $this->askPrice()) < $this->volumeUSDAsk()) {
      $rounded += 10 ** -($this::BTC_FIDELITY - 1);
    }
    return $rounded;
  }

  protected function askPriceEffective()
  {
    return ($this->askPrice() * $this->askBTCVolume() - $this->feeAbsolute()) / $this->askBTCVolume();
  }

  /**
   * DIFF
   */

  public function profitBTC()
  {
    return $this->bidBTCVolume() * (1 - $this->fee()) - $this->askBTCVolume() * (1 + $this->fee());
  }

  public function profitUSD() {
    return floor(($this->volumeUSDAskPostFees() - $this->volumeUSDBidPostFees()) * 100) / 100;
  }

  public function midprice() {
    return ($this->bidPrice() + $this->askPrice()) / 2;
  }

  public function dupes() {
    $base_search_params = [
      'key' => 'price',
      'unit' => '=',
      'operator' => '~',
    ];

    $bid_dupes = $this->openOrders->search([
      'range' => $this->bidPrice() * $this::DUPE_RANGE_MULTIPLIER,
      'value' => $this->bidPrice(),
      'type' => $this->openOrders->typeBuy(),
    ] + $base_search_params);

    $ask_dupes = $this->openOrders->search([
      'range' => $this->askPrice() * $this::DUPE_RANGE_MULTIPLIER,
      'value' => $this->askPrice(),
      'type' => $this->openOrders->typeSell(),
    ] + $base_search_params);

    return [
      'bids' => $bid_dupes,
      'asks' => $ask_dupes,
    ];
  }

  public function execute() {
    $this->sell
      ->setParam('price', $this->askPrice())
      ->setParam('amount', $this->askBTCVolume())
      ->execute();

    $this->buy
      ->setParam('price', $this->bidPrice())
      ->setParam('amount', $this->bidBTCVolume())
      ->execute();
  }

  /**
   * @Assert\True(message="This trade is not profitable")
   */
  public function isProfitable()
  {
    return $this->profitUSD() >= round($this::MIN_PROFIT_USD, 2) && $this->profitBTC() > 0;
  }

  /**
   * @assert\False(message="There are currently dupes")
   */
  public function hasDupes() {
    return !empty($this->dupes()['bids']) || !empty($this->dupes()['asks']);
  }
}
