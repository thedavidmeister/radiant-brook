<?php

namespace AppBundle;

use Dotenv\Dotenv;
use Dotenv\Loader;
use AppBundle\Ensure;

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
    public function dotEnvPath()
    {
        return __DIR__;
    }

    /**
     * Sets an environment variable in a Dotenv compatible way.
     *
     * @param string $name
     *   The environment variable name to set.
     *
     * @param string $value
     *   The value of the environment variable to set.
     */
    public function set($name, $value)
    {
        Ensure::isValidVariableName($name);
        Ensure::isString($value);

        // Get a mutable loader.
        $loader = new Loader($this->dotEnvPath());
        $loader->setEnvironmentVariable($name, $value);
    }

    /**
     * Clears an environment variable in a Dotenv compatible way.
     *
     * @param string $name
     *   The environment variable to clear.
     *
     * @see https://github.com/vlucas/phpdotenv/issues/106
     */
    public function clear($name)
    {
        Ensure::isValidVariableName($name);

        putenv($name);
        unset($_ENV[$name]);
        unset($_SERVER[$name]);
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
        Ensure::isValidVariableName($name);

        $loader = new Loader($this->dotEnvPath());

        // If the environment variable is already set, don't try to use Dotenv
        // as an exception will be thrown if a .env file cannot be found.
        if (null === $value = $loader->getEnvironmentVariable($name)) {
            try {
                // Attempt to load environment variables from .env if we didn't
                // already have what we were looking for in memory.
                $dotenv = new Dotenv($this->dotEnvPath());
                $dotenv->load($this->dotEnvPath());
            } catch (\Exception $e) {
                // Provide a more useful message than the Dotenv default.
                throw new \Exception('Loading .env file failed while attempting to access environment variable ' . $name);
            }

            // Try once more to find what we're looking for, then give up.
            if (null === $value = $loader->getEnvironmentVariable($name)) {
                throw new \Exception('Loading .env file failed while attempting to access environment variable ' . $name);
            }
        }

        return $value;
    }
}
