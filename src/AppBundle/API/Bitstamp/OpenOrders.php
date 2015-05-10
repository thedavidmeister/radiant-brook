<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\PrivateBitstampAPI;

class OpenOrders extends PrivateBitstampAPI
{
    const ENDPOINT = 'open_orders';

    const TYPE_BUY = 0;

    const TYPE_SELL = 1;

    const SEARCH_PRECISION = 2;

    protected $_data;

    public function requiredParams() 
    {
        return [];
    }

    public function typeSell() 
    {
        return $this::TYPE_SELL;
    }

    public function typeBuy() 
    {
        return $this::TYPE_BUY;
    }

    public function data() 
    {
        if (!isset($this->_data)) {
            $this->_data = $this->execute();
        }
        return (array) $this->_data;
    }

    /**
   * Return a filtered array of all open order data matching search parameters.
   *
   * @todo Test this.
   *
   * @param array $params
   *   Looks like:
   *   [
   *     // The key to search for.
   *     'key' => 'price, amount',
   *     // The value to compare orders against.
   *     'value' => <float goes here>,
   *     // Equals, less than, great than, roughly.
   *     'operator' => '=, <, >, ~',
   *     // Range, only applies to >< operator.
   *     'range' => <float goes here>
   *     // The type (optional).
   *     'type' => TYPE_BUY, TYPE_SELL,
   *   ]
   * @return [type]         [description]
   */
    public function search($params) 
    {
        // Don't attempt malformed searches.
        $requireds = ['key', 'value', 'operator'];
        foreach ($requireds as $required) {
            if (!isset($params[$required])) {
                throw new \Exception("$required must be set");
            }
        }

        $found = [];
        $params['value'] = (float) round($params['value'], $this::SEARCH_PRECISION);
        foreach ($this->data() as $order) {
            $check = (float) round($order[$params['key']], $this::SEARCH_PRECISION);
            switch ($params['operator']) {
                case '=':
                    if ($check == $params['value']) {
                        $found[] = $order;
                    }
                    break;

                case '<':
                    if ($check < $params['value']) {
                        $found[] = $order;
                    }
                    break;

                case '>':
                    if ($check > $params['value']) {
                        $found[] = $order;
                    }
                    break;

                case '~':
                    if (
                    $check > $params['value'] - $params['range']
                    && $check < $params['value'] + $params['range']
                    ) {
                        $found[] = $order;
                    }
                    break;

                default:
                    throw new \Exception('Unknown operator');
                break;
            }
        }

        // Filter out orders of the wrong type.
        if (isset($params['type'])) {
            foreach ($found as $key => $order) {
                if ($order['type'] !== $params['type']) {
                    unset($found[$key]);
                }
            }
        }
        return $found;
    }
}
