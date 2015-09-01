<?php

use AppBundle\Secrets;
use AppBundle\Cast;

$secrets = new Secrets();

// Move environment variables to parameters if we are not in a CI environment.
// In CI, we want all environment variables and similar to be set explicitly by
// the test suite.
if (!Cast::toBoolean($secrets->get('CI'))) {
  $container->setParameter('bitstamp.price_proposer.min_max_step', [$secrets->get('BITSTAMP_PERCENTILE_MIN'), $secrets->get('BITSTAMP_PERCENTILE_MAX'), $secrets->get('BITSTAMP_PERCENTILE_STEP')]);
}

