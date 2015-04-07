<?php

namespace AppBundle\API\Bitstamp;

class OrderList
{
  protected $data;

  function __construct($data) {
    $this->data = $data;
  }

  // @todo test me.
  protected function sortAsc()
  {
    usort($this->data, function($a, $b) {
      if ($a[0] == $b[0]) {
        return 0;
      }
      return $a[0] < $b[0] ? -1 : 1;
    });
  }

  protected function sortDesc() {
    usort($this->data, function($a, $b) {
      if ($a[0] == $b[0]) {
        return 0;
      }
      return $a[0] > $b[0] ? -1 : 1;
    });
  }

  /**
   * Calculate a percentile.
   *
   * @param float $pc
   *   Float between 0 - 1 represending the percentile.
   *
   * @return float
   *   The price at the given percentile.
   */
  public function percentile($pc) {
    if ($pc < 0 || $pc > 1) {
      throw new \Exception('Percentage must be between 0 - 1.');
    }
    $index = $pc * $this->totalVolume();
    $this->sortAsc();

    $sum = 0;
    foreach ($this->data as $datum) {
      $sum += $datum[1];
      if ($index <= $sum) {
        return $datum;
      }
    }
  }

  public function min()
  {
    $this->sortAsc();
    return $this->data[0];
  }

  public function max()
  {
    $this->sortDesc();
    return $this->data[0];
  }

  public function totalVolume()
  {
    $sum = 0;
    foreach ($this->data as $datum) {
      $sum += $datum[1];
    }
    return $sum;
  }

  public function totalCap() {
    $sum = 0;
    foreach ($this->data as $datum) {
      $sum += $datum[0] * $datum[1];
    }
    return $sum;
  }

  public function percentCap($pc) {
    if ($pc < 0 || $pc > 1) {
      throw new \Exception('Percentage must be between 0 - 1.');
    }

    $index = $pc * $this->totalCap();
    $this->sortAsc();

    $sum = 0;
    foreach ($this->data as $datum) {
      $sum += $datum[0] * $datum[1];
      if ($index <= $sum) {
        return $datum;
      }
    }
  }

}
