<?php

namespace AppBundle;

/**
 * Handles things that need to be secret.
 */
class Secrets
{
    /**
     * Extracts secret keys from the file system or environment variables.
     *
     * @param string $name
     *   THe name of the secret to get.
     *
     * @return string
     *   Returns the secret if found.
     */
    public function get($name)
    {
        // First try environment variables.
        if (getenv($name)) {
            return trim(getenv($name));
        }

        if (file_exists(__DIR__ . '/Resources/keys/' . $name)) {
            return trim(file_get_contents(__DIR__ . '/Resources/keys/' . $name));
        }
    }
}
