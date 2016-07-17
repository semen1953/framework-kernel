<?php
declare(strict_types=1);

namespace Comely\Framework;

use Comely\Framework\Kernel\Config;
use Comely\Framework\Kernel\Constants;
use Comely\Framework\Kernel\DatabasesTrait;
use Comely\Framework\Kernel\DateTime;
use Comely\Framework\Kernel\ErrorHandler;
use Comely\Framework\Kernel\InstancesTrait;
use Comely\Framework\Kernel\PropertiesTrait;
use Comely\IO\DependencyInjection\Container;

class Kernel implements Constants
{
    private $container;
    private $dateTime;
    private $errorHandler;
    private $bootstrapped   =   false;
    
    use DatabasesTrait;
    use InstancesTrait;
    use PropertiesTrait;

    /**
     * Framework Kernel constructor.
     *
     * @param array $components
     * @param string $env
     */
    public function __construct(array $components, string $env)
    {
        // Create a private dependency injection container
        $this->container    =   new Container();

        // Run through all passed components
        foreach($components as $component)
        {
            /// Add to container
            $this->container->add(
                $component,
                \Comely::baseClassName(
                    is_object($component) ? get_class($component) : $component
                )
            );
        }

        // Set variables
        $this->env  =   $env;
        $this->rootPath =   ".";
        $this->dateTime =   new DateTime();
        $this->errorHandler =   new ErrorHandler();
    }

    /**
     * Load compiled configuration from cache if available
     */
    public function loadCachedConfig() : self
    {
        
        return $this;
    }

    /**
     * @return Kernel
     * @throws KernelException
     */
    public function bootstrap() : self
    {
        if($this->bootstrapped) {
            // Already bootstrapped?
            throw KernelException::bootstrapped();
        }
        
        // Read configuration
        if(!$this->config instanceof Config) {
            $this->config   =   new Config($this->rootPath . Kernel::DS . Kernel::CONFIG_PATH);
        }

        return $this;
    }
}