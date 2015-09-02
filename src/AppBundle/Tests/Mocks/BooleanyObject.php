<?php

namespace AppBundle\Tests\Mocks;

/**
 * Dummy to test objects that cast to a boolean-y value.
 */
class BooleanyObject
{
    protected $potentialStrings = [];

    /**
     * {@inheritdoc}
     */
    public function __construct($truthy = null)
    {
        if ((bool) $truthy !== $truthy && $truthy !== null) {
            throw new \Exception('BooleanyObject needs to be passed a boolean or null.');
        }

        if ($truthy === true || $truthy === null) {
            $this->potentialStrings = array_merge($this->potentialStrings, ['true', 'yes']);
        }

        if ($truthy === false || $truthy === null) {
            $this->potentialStrings = array_merge($this->potentialStrings, ['false', 'no', '']);
        }

        shuffle($this->potentialStrings);
    }

    /**
     * Implements __toString().
     *
     * The string returned is a random boolean-y string.
     *
     * @return string
     */
    public function __toString()
    {
        return reset($this->potentialStrings);
    }
}
