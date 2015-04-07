<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\APIInterface;

abstract class BitstampAPI implements APIInterface
{
  const DOMAIN = 'https://www.bistamp.net/api/';

  const ENDPOINT = 'override me';

  public function getDomain() {
    return $this::DOMAIN;
  }

  public function getEndpoint() {
    return $this::ENDPOINT;
  }

}
