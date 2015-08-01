<?php

namespace AppBundle;

use Dotenv\Dotenv;
use Dotenv\Loader;

/**
 * Handles things that need to be secret by reading things from env.
 */
class Secrets
{
    /**
    * Returns the path that Dotenv should scan for a .env file.
    *
    * On Acquia, we need to handle their symlinky file system structure. On local
    * dev we can simply dump our .env file into sites/all.
    *
    * @return string
    *   Path to the directory containing .env.
    */
    protected function dotEnvPath()
    {
        return __DIR__;
    }

    /**
     * Sets an environment variable in a Dotenv compatible way.
     *
     * @param string $key
     *   The environment variable name to set.
     * @param string $value
     *   The value of the environment variable to set.
     */
    public function set($key, $value)
    {
        // Get a mutable loader.
        $loader = new Loader($this->dotEnvPath());
        $loader->setEnvironmentVariable($key, $value);
    }

    /**
     * Clears an environment variable in a Dotenv compatible way.
     *
     * @param string $key
     *   The environment variable to clear.
     *
     * @see https://github.com/vlucas/phpdotenv/issues/106
     */
    public function clear($key)
    {
        putenv($key);
        unset($_ENV[$key]);
        unset($_SERVER[$key]);
    }

    /**
     * Gets environment variables, or dies trying.
     *
     * @param string $name
     *   The name of the environment variable to get.
     *
     * @return string
     *   The environment variable found.
     */
    public function get($name)
    {
        if (!is_string($name)) {
            throw new \Exception('Environment variables must be a string');
        }

        $loader = new Loader($this->dotEnvPath());

        // If the environment variable is already set, don't try to use Dotenv
        // as an exception will be thrown if a .env file cannot be found.
        if (null === $value = $loader->getEnvironmentVariable($name)) {
            // Attempt to load environment variables from .env if we didn't
            // already have what we were looking for in memory.
            $dotenv = new Dotenv($this->dotEnvPath());
            $dotenv->load($this->dotEnvPath());

            // Try once more to find what we're looking for, then give up.
            if (null === $value = $loader->getEnvironmentVariable($name)) {
                throw new \Exception('Environment variable not found: ' . $name . ' - This probably means you did not set your .env file up properly, you dingus.');
            }
        }

        return $value;
    }
}
