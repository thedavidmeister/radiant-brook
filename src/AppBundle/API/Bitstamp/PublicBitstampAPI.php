<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;

abstract class PublicBitstampAPI extends BitstampAPI
{

  protected $data;

  public function data()
  {
    if (!isset($this->data)) {
      $this->data = $this->client->get($this->url())->json();
    }

    return $this->data;
  }

}
