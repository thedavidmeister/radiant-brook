<?php

use AppBundle\Secrets;

$secrets = new Secrets();

$container->setParameter('bitstamp.price_proposer.min_max_step', [$secrets->get('BITSTAMP_PERCENTILE_MIN'), $secrets->get('BITSTAMP_PERCENTILE_MAX'), $secrets->get('BITSTAMP_PERCENTILE_STEP')]);
