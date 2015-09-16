<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * AppBundle.
 */
class AppBundle extends Bundle
{
    /**
     * { @inheritdoc }
     */
    public function boot()
    {
        // If the PHP environment does not support 64 bit operations our integer
        // math on market capitalization can fail - the numbers we're dealing with
        // exceed the 32 bit bounds when cap is measured in satoshis.
        if (!$this->isPhpEnvironment64Bits()) {
            // @codeCoverageIgnoreStart
            throw new \Exception('This is not a 64 bit PHP environment. Important math operations are unsupported without a 64 bit environment!');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Detects a 64 bit PHP environment.
     *
     * @see http://stackoverflow.com/questions/2353473/can-php-tell-if-the-server-os-it-64-bit
     * @return bool
     *   true if a 64 bit environment.
     */
    protected function isPhpEnvironment64Bits()
    {
        return strlen(decbin(~0)) === 64;
    }
}
