<?php

namespace AppBundle;

use Dotenv;

/**
 * Handles things that need to be secret.
 */
class Secrets
{
    public function __construct() {
        Dotenv::load(__DIR__);
    }

    /**
     * Extracts secret keys from the file system or environment variables.
     *
     * @param string $name
     *   The name of the secret to get.
     *
     * @return string
     *   Returns the secret if found.
     */
    public function get($name)
    {
        if ($value = getenv($name)) {
            return trim($value);
        }

        throw new \Exception('Secret not found: ' . $name);
    }
}
