<?php

namespace AppBundle\Tests\Snapshot;

use AppBundle\SnapshotBitstamp;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests \AppBundle\SnapshotBitsamp
 */
abstract class SnapshotBitstampTest extends WebTestCase
{

    protected function mockLogger()
    {
        $logger = $this
            ->getMockBuilder('\Psr\Log\LoggerInterface')
            ->getMock();

        return $logger;
    }

    protected function mockKeenIOClient()
    {
      $keenio = $this
        ->getMockBuilder('\KeenIO\Client\KeenIOClient')
        ->disableOriginalConstructor()
        ->getMock();

      $keenio
        ->method('factory')
        ->will($this->returnSelf());

      return $keenio;
    }

    protected function mockSecrets()
    {
        $secrets = $this
          ->getMockBuilder('\AppBundle\Secrets')
          ->getMock();

        return $secrets;
    }

    protected function mockDataProvider()
    {
      $dataProvider = $this
        ->getMockBuilder($this->dataProviderClass)
        ->disableOriginalConstructor()
        ->getMock();

      $dataProvider
        ->method('data')
        ->willReturn(['data' => 'foo']);

      return $dataProvider;
    }

    /**
     * Returns an API object from $this->className with Mocks preconfigured.
     *
     * @return mixed
     */
    protected function getClass()
    {
          $class = new $this->className($this->mockDataProvider());
          // $class->setLogger($this->mockLogger());
          $class->setKeenIOClient($this->mockKeenIOClient());
          // $class->setSecrets($this->mockSecrets());

          return $class;
    }

    public function testData()
    {
      $this->getClass();
      // $this->assertEquals(['data' => 'foo'], $this->getClass()->data());
    }

}
