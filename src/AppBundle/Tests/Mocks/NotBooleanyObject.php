<?php

namespace AppBundle\Tests\Mocks;

/**
 * Dummy to test objects that cast to a non-boolean string.
 */
// @codingStandardsIgnoreStart
class NotBooleanyObject
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return uniqid();
    }
}
