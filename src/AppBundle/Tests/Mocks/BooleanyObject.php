<?php

namespace AppBundle\Tests\Mocks;

use Respect\Validation\Validator as v;

/**
 * Dummy to test objects that cast to a boolean-y value.
 */
class BooleanyObject
{
    protected $potentialStrings = [];

    protected function addPotentialStrings(array $toAdd)
    {
        v::each(v::string())->check($toAdd);

        $this->potentialStrings = array_merge($this->potentialStrings, $toAdd);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool|null $truthy
     *   If a boolean is passed, the string this object casts to will be
     *   a boolean-y string that matches the passed boolean's truthy-ness. If
     *   null is passed this object can cast to any boolean-y string.
     */
    public function __construct($truthy = null)
    {
        if ((bool) $truthy !== $truthy && $truthy !== null) {
            throw new \Exception('BooleanyObject needs to be passed a boolean or null.');
        }

        if ($truthy === true || $truthy === null) {
            $this->addPotentialStrings(['true', 'yes']);
        }

        if ($truthy === false || $truthy === null) {
            $this->addPotentialStrings(['false', 'no', '']);
        }

        shuffle($this->potentialStrings);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return (string) reset($this->potentialStrings);
    }
}
