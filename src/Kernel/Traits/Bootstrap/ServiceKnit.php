<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits\Bootstrap;

use Comely\Framework\KernelException;
use Comely\Knit;

/**
 * Class ServiceKnit
 * @package Comely\Framework\Kernel\Traits\Bootstrap
 */
trait ServiceKnit
{
    /**
     * Error codes 20078-20079
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

        // Compiler path
        if(!property_exists($this->config->app->knit, "compilerPath")) {
            throw KernelException::bootstrapError(
                '"compiler_path" must be set under "knit" node', 20079
            );
        }

        try {
            $this->knit->setCompilerPath($this->rootPath . self::DS . $this->config->app->knit->compilerPath);

            // Caching
            if(property_exists($this->config->app->knit, "caching")) {
                switch ($this->config->app->knit->caching)
                {
                    case "static":
                    case 1:
                        $this->knit->setCachePath($this->getDisk("cache"))
                            ->setCaching(Knit::CACHE_STATIC);
                        break;
                    case "dynamic":
                    case 2:
                        $this->knit->setCachePath($this->getDisk("cache"))
                            ->setCaching(Knit::CACHE_DYNAMIC);
                        break;
                }
            }
        } catch(\ComelyException $e) {
            throw KernelException::bootstrapError($e->getMessage());
        }
    }
}