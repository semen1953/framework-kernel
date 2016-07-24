<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits\Bootstrap;
use Comely\Knit;

/**
 * Class ServiceKnit
 * @package Comely\Framework\Kernel\Traits\Bootstrap
 */
trait ServiceKnit
{
    /**
     * Error codes 20078
     */
    private function registerKnit()
    {
        // Container has Knit component, must be defined in config
        if(!property_exists($this->config->app, "knit")) {
            throw KernelException::bootstrapError(
                '"knit" node must be set under "app" node', 20078
            );
        }

        // Bootstrap knit
        $this->knit =   $this->container->get("Knit");
        
    }
}