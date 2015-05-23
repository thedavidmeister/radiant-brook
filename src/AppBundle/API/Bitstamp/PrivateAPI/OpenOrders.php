<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp open orders private API endpoint wrapper.
 *
 * This API call is cached for 10 seconds.
 */
class OpenOrders extends PrivateAPI
{
    // {@inheritdoc}
    const ENDPOINT = 'open_orders';

    // Bitstamp representation of a "buy" order type.
    const TYPE_BUY = 0;

    // Bitstamp representation of a "sell" order type.
    const TYPE_SELL = 1;

    // The precision to use in float comparison for search().
    const SEARCH_PRECISION = 2;

    // The key for price of an open order.
    const KEY_PRICE = 'price';

    // The key for amount of an open order.
    const KEY_AMOUNT = 'amount';

    /**
     * Returns the Bitstamp representation of a "sell" order type.
     *
     * @return int
     */
    public function typeSell()
    {
        return $this::TYPE_SELL;
    }

    /**
     * Returns the Bitstamp representation of a "buy" order type.
     *
     * @return int
     */
    public function typeBuy()
    {
        return $this::TYPE_BUY;
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
                throw new \Exception('Search parameter "' . $required . '" must be set');
            }
        }

        $allowed_keys = [self::KEY_PRICE, self::KEY_AMOUNT];
        if (!in_array($params['key'], $allowed_keys))
        {
            throw new \Exception('Unknown search key: ' . $params['key']);
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
