<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp OpenOrders class.
 */
class OpenOrdersTest extends PrivateAPITest
{
    protected $endpoint = 'open_orders';
    protected $sample = '[{"price":"237.50","amount":"0.03397937","type":1,"id":67290521,"datetime":"2015-05-16 21:30:19"},{"price":"232.95","amount":"0.03434213","type":0,"id":67290522,"datetime":"2015-05-16 21:30:19"},{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]';
    protected $sample2 = '[{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\OpenOrders';
}
