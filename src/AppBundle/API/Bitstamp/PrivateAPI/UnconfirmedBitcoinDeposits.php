<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Unconfirmed Bitcoin Deposits Bitstamp API endpoint wrapper.
 *
 * This API call is cached for 60 seconds.
 */
class UnconfirmedBitcoinDeposits extends PrivateAPI
{
    const ENDPOINT = 'unconfirmed_btc';
}
