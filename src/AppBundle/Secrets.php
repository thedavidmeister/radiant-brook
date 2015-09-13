<?php

namespace AppBundle;

use Dotenv\Dotenv;
use Dotenv\Loader;
use Respect\Validation\Validator as v;

/**
 * Handles things that need to be secret by reading things from env.
 */
class Secrets
{
    const MISSING_ENV_EXCEPTION_MESSAGE = 'Loading .env file failed while attempting to access environment variable ';
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
        v::PHPLabel()->check($name);
        v::string()->check($value);

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
        v::PHPLabel()->check($name);

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
        v::PHPLabel()->check($name);

        $loader = new Loader($this->dotEnvPath());

        // If the environment variable is already set, don't try to use Dotenv
        // as an exception will be thrown if a .env file cannot be found.
        $value = $loader->getEnvironmentVariable($name);
        if (!isset($value)) {
            try {
                // Attempt to load environment variables from .env if we didn't
                // already have what we were looking for in memory.
                $dotenv = new Dotenv($this->dotEnvPath());
                $dotenv->load($this->dotEnvPath());

                // Try once more to find what we're looking for before giving
                // up. It is not possible to test this on infrastructure with
                // .env missing and it is not possible to test the above
                // exception where it is set. We have to ignore this for code
                // coverage reports.
                // @codeCoverageIgnoreStart
                $value = $loader->getEnvironmentVariable($name);
                if (!isset($value)) {
                    throw new \Exception(self::MISSING_ENV_EXCEPTION_MESSAGE . $name);
                }
                // Code coverage requires this return.
                return $value;
                // @codeCoverageIgnoreEnd
            } catch (\Exception $e) {
                // Provide a more useful message than the Dotenv default.
                throw new \Exception(self::MISSING_ENV_EXCEPTION_MESSAGE . $name);
            }
        }

        return $value;
    }
}
