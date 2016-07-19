<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits\Bootstrap;

/**
 * Class ServiceCipher
 * @package Comely\Framework\Kernel\Traits\Bootstrap
 */
trait ServiceCipher
{
    /**
     * Configure Cipher Component
     */
    private function registerCipher()
    {
        // Cipher Key
        if(
            property_exists($this->config->app->security, "cipherKey")  &&
            !empty($this->config->app->security->cipherKey)
        ) {
            $this->cipher->defaultSecret($this->config->app->security->cipherKey);
        }

        // Default hashing algorithm
        if(
            property_exists($this->config->app->security, "defaultHashAlgo")    &&
            !empty($this->config->app->security->defaultHashAlgo)
        ) {
            $this->cipher->defaultHashAlgo($this->config->app->security->defaultHashAlgo);
        }
    }
}