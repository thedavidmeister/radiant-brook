<?php

namespace AppBundle\API\Bitstamp\PrivateAPI;

/**
 * Bitstamp bitcoin deposit address private API endpoint wrapper.
 *
 * Returns your bitcoin deposit address.
 */
class BitcoinDepositAddress extends AbstractPrivateAPI
{
    const ENDPOINT = 'bitcoin_deposit_address';
}
