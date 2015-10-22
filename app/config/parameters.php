<?php

use AppBundle\Secrets;

$secrets = new Secrets();

// Move environment variables to parameters.
$container->setParameter('bitstamp.price_proposer.min_max_step', [$secrets->get('BITSTAMP_PERCENTILE_MIN'), $secrets->get('BITSTAMP_PERCENTILE_MAX'), $secrets->get('BITSTAMP_PERCENTILE_STEP')]);

// Handle database details.
$url = parse_url($secrets->get('DATABASE_URL'));

$container->setParameter('database_host', $url['host']);
$container->setParameter('database_port', $url['port']);
$container->setParameter('database_name', substr($url['path'], 1));
$container->setParameter('database_user', $url['user']);
$container->setParameter('database_password', $url['pass']);
