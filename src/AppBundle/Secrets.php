<?php

namespace AppBundle;

use Dotenv;

/**
 * Handles things that need to be secret by reading things from env.
 */
class Secrets
{
    public function __construct() {
        // Ensure that any secrets in .env are loaded.
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

        // If some class needs a secret, we cannot accept it not being available
        // as functionality will surely rely on it.
        throw new \Exception('Secret not found: ' . $name);
    }
}
