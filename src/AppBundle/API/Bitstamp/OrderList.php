<?php

namespace AppBundle\API\Bitstamp;

/**
 * Wraps a list of orders provided by Bitstamp to handle some basic statistics.
 */
class OrderList
{
    protected $data;

    /**
     * Constructor.
     *
     * @param array $data
     *   Order list data from Bitstamp. Either the 'bids' or 'asks' array from
     *   a full order book array.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Expose the data without allowing modification.
     *
     * @return array
     *   The internal data array.
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Utility function for sorting ascending.
     *
     * @todo Test me.
     */
    protected function sortAsc()
    {
        usort($this->data, function($a, $b) {
            if ($a[0] == $b[0]) {
                return 0;
            }

            return $a[0] < $b[0] ? -1 : 1;
        });
    }

    /**
     * Utility function for sorting descending.
     */
    protected function sortDesc()
    {
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
    public function percentile($pc)
    {
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

    /**
     * Returns the minimum value of the order list.
     *
     * @return array
     *   The minimum order.
     */
    public function min()
    {
        $this->sortAsc();

        return $this->data[0];
    }

    /**
     * Returns the maximum value of the order list.
     *
     * @return array
     *   The maximum order.
     */
    public function max()
    {
        $this->sortDesc();

        return $this->data[0];
    }

    /**
     * Calculates the total BTC Volume of the order list.
     *
     * @return float
     *   The total BTC Volume of the order list.
     */
    public function totalVolume()
    {
        $sum = 0;
        foreach ($this->data as $datum) {
            $sum += $datum[1];
        }

        return $sum;
    }

    /**
     * Calculates total capitalisation of the order list.
     *
     * @return float
     *   The total capitalisation of the order list.
     */
    public function totalCap()
    {
        $sum = 0;
        foreach ($this->data as $datum) {
            $sum += $datum[0] * $datum[1];
        }

        return $sum;
    }

    /**
     * Calculates a given percentile of order list capitalisation.
     *
     * @param float $pc
     *   Percentile to calculate. Must be between 0 - 1.
     *
     * @return array
     *   The order representing the requested percentile.
     */
    public function percentCap($pc)
    {
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
