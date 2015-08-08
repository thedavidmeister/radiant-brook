<?php

namespace AppBundle\Tests;

use AppBundle\Ensure;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests \AppBundle\Ensure
 */
class EnsureTest extends WebTestCase
{
  /**
   * Test set().
   *
   * @group stable
   */
  public function testSet() {
    // Everything is set... try a few things.
    $tests = [
      'foo',
      '',
      1,
      '1',
      0,
      new \DateTime(),
      [],
    ];
    array_walk($tests, function($test) {
      $this->assertSame($test, Ensure::set($test));
    });
  }

  /**
   * Test exceptions from set().
   *
   * @group stable
   */
  public function testSetExceptions() {
    $this->setExpectedException('Exception', 'NULL is not set.');
    Ensure::set(NULL);
  }

  /**
   * Data provider for testIsEmptyExceptions().
   * @return array
   */
  public function dataIsEmptyExceptions() {
    return [
      [1, '1 is not empty.'],
      ['1', '"1" is not empty.'],
      [[NULL], '[null] is not empty.'],
      [new \StdClass(), '{} is not empty.'],
    ];
  }

  public function dataNotEmptyExceptions() {
    return [
      ['', '"" is empty.'],
      [0, '0 is empty.'],
      ['0', '"0" is empty.'],
      [[], '[] is empty.'],
      [NULL, 'null is empty.'],
    ];
  }

  /**
   * Test isEmpty().
   *
   * @dataProvider dataNotEmptyExceptions
   * @group stable
   */
  public function testIsEmpty($empty, $message) {
      $this->assertSame($empty, Ensure::isEmpty($empty));
  }

  /**
   * Test exceptions from isEmpty().
   *
   * @dataProvider dataIsEmptyExceptions
   * @group stable
   */
  public function testIsEmptyExceptions($notEmpty, $message) {
    $this->setExpectedException('Exception' , $message);
    Ensure::isEmpty($notEmpty);
  }

  /**
   * Test notEmpty().
   *
   * @dataProvider dataIsEmptyExceptions
   * @group stable
   */
  public function testNotEmpty($notEmpty, $message) {
    $this->assertSame($notEmpty, Ensure::notEmpty($notEmpty));
  }

  /**
   * Test exceptions for notEmpty().
   *
   * @dataProvider dataNotEmptyExceptions
   * @group stable
   */
  public function testNotEmptyExceptions($empty, $message) {
    $this->setExpectedException('Exception' , $message);
    Ensure::notEmpty($empty);
  }

  /**
   * Test isInt().
   *
   * @group stable
   */
  public function testIsInt() {
    $i = -5;
    while ($i <= 5) {
      $this->assertSame($i, Ensure::isInt($i));
      $this->assertSame($i, Ensure::isInt((string) $i));
      $i++;
    }
    $this->assertSame(PHP_INT_MAX, Ensure::isInt(PHP_INT_MAX));
  }

  /**
   * Data provider for dataIsIntExceptions
   */
  public function dataIsIntExceptions() {
    return [
      [1.1, '1.1 is not an int.'],
      ['foo', '"foo" is not an int.'],
      [NULL, 'null is not an int.'],
      [[], '[] is not an int.'],
    ];
  }

  /**
   * Test exceptions for isInt().
   *
   * @dataProvider dataIsIntExceptions
   * @group stable
   */
  public function testIsIntExceptions($notInt, $message) {
    $this->setExpectedException('Exception', $message);
    Ensure::isInt($notInt);
  }
}
