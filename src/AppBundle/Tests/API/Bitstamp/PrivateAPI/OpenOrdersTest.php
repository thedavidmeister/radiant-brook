<?php

namespace AppBundle\Tests\API\Bitstamp\PrivateAPI;

/**
 * Tests the Bitstamp OpenOrders class.
 */
class OpenOrdersTest extends PrivateAPITest
{
    protected $endpoint = 'open_orders';
    protected $sample = '{"data":[{"price":"237.50","amount":"0.03397937","type":1,"id":67290521,"datetime":"2015-05-16 21:30:19"},{"price":"232.95","amount":"0.03434213","type":0,"id":67290522,"datetime":"2015-05-16 21:30:19"},{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]}';
    protected $sample2 = '{"data":[{"price":"241.45","amount":"0.03342358","type":1,"id":67009615,"datetime":"2015-05-14 01:30:54"},{"price":"246.00","amount":"0.03280538","type":1,"id":66672917,"datetime":"2015-05-10 12:17:32"}]}';
    protected $className = 'AppBundle\API\Bitstamp\PrivateAPI\OpenOrders';

    /**
     * Test that sending an unknown key to search() throws an exception.
     *
     * @expectedException Exception
     * @expectedExceptionMessage Unknown search key: foo
     * @group stable
     */
    public function testSearchKeyException() {
      $this->getClass()->search(['key' => 'foo', 'value' => rand(), 'operator' => rand()]);
    }

    /**
     * Test that constants set by Bitstamp are represented correctly.
     *
     * @group stable
     */
    public function testBitstampConstants() {
      $class = $this->getClass();

      $this->assertSame(0, $class->typeBuy());
      $this->assertSame(1, $class->typeSell());
    }

    /**
     * Data provider for testSearchExceptions()
     */
    public function dataSearchExceptions() {
      // We only have 7 permutations so just test them all.
      $key_not_found = 'Search parameter "key" must be set';
      $value_not_found = 'Search parameter "value" must be set';
      $operator_not_found = 'Search parameter "operator" must be set';
      return [
        [[], $key_not_found],
        [['value' => rand()], $key_not_found],
        [['value' => rand(), 'operator' => rand()], $key_not_found],
        [['operator' => rand()], $key_not_found],
        [['key' => rand()], $value_not_found],
        [['key' => rand(), 'operator' => rand()], $value_not_found],
        [['key' => rand(), 'value' => rand()], $operator_not_found],
      ];

    }

    /**
     * Tests exceptions thrown by search().
     *
     * @dataProvider dataSearchExceptions
     * @group stable
     */
    public function testSearchExceptions($params, $message) {
      $class = $this->getClass();

      $this->setExpectedException('Exception', $message);

      $class->search($params);
    }
}
