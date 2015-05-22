<?php

namespace AppBundle\Tests\API\Bitstamp;

use AppBundle\API\Bitstamp\API;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use AppBundle\Tests\GuzzleTestTrait;

/**
 * Test the API base class directly.
 */
class APIBaseTest extends WebTestCase
{
    use GuzzleTestTrait;

    protected function getClass()
    {
        return new API($this->client(), $this->mockLogger());
    }

    /**
     * Test that parameter validation is called by default.
     *
     * @expectedException Exception
     * @expectedExceptionMessage Parmeter foobar cannot be set to bazbing.
     * @group stable
     */
    public function testDefaultParamValidation()
    {
        $this->getClass()->setParam('foobar', 'bazbing');
    }

    /**
     * Tests we can't accidentally create child API classes without an endpoint.
     *
     * This doesn't really need to be run for every child test...
     *
     * @expectedException Exception
     * @expectedExceptionMessage The Bitstamp API endpoint has not been set for this class.
     * @group stable
     */
    public function testEndpointException()
    {
        $this->getClass()->execute();
    }
}
