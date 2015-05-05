<?php

namespace AppBundle\API\Bitstamp;

use AppBundle\API\Bitstamp\BitstampAPI;

abstract class PublicBitstampAPI extends BitstampAPI
{

  protected $data;

  // The DateTime of the last data save.
  protected $datetime;

  public function data()
  {
    if (!isset($this->data)) {
      $this->datetime = new \DateTime();
      $this->data = $this->client->get($this->url())->json();
    }

    // @todo - add logging!
    return $this->data;
  }

  public function datetime() {
    return $this->datetime;
  }

}
