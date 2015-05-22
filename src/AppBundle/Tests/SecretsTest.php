<?php

namespace AppBundle\Tests;

use AppBundle\Secrets;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecretsTest extends WebTestCase
{
  protected function secrets()
  {
    return new Secrets();
  }

  /**
   * Tests that secrets can find variables in the file system.
   */
  public function testSecretsFS() {

  }

  /**
   * Tests that secrets can find variables in the environment.
   *
   * @group stable
   */
  public function testSecretsEnv()
  {
    putenv('FOOBAR=BINGBAZ');
    $this->assertSame('BINGBAZ', $this->secrets()->get('FOOBAR'));
  }

  /**
   * Tests exceptions thrown when secrets are not found.
   *
   * @expectedException Exception
   * @expectedExceptionMessage Secret not found:
   * @group stable
   */
  public function testSecretsException() {
    $this->secrets()->get('no match');
  }
}
