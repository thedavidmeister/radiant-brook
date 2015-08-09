<?php

namespace AppBundle\Tests;

use AppBundle\Secrets;

trait EnvironmentTestTrait
{
    protected $overriddenEnv = [];

    /**
     * Set environment variables in a way that we can clear them post-suite.
     *
     * If we set environment variables without tracking what we set, we cannot
     * clean them up later. If we cannot clean them up later, future usage of
     * Secrets will inherit our cruft and break future tests.
     *
     * @param string $key
     *   The key to set.
     * @param string $value
     *   The value to set.
     *
     * @see clearEnv()
     */
    protected function setEnv($key, $value)
    {
        $this->overriddenEnv[] = $key;
        $this->overriddenEnv = array_unique($this->overriddenEnv);

        $secrets = new Secrets();
        $secrets->set($key, (string) $value);
    }

    protected function clearEnv($key)
    {
        $secrets = new Secrets();
        $secrets->clear($key);
    }

    protected function clearAllSetEnv()
    {
        array_walk($this->overriddenEnv, [$this, 'clearEnv']);
    }

    protected function tearDown()
    {
        $this->clearAllSetEnv();
    }
}
