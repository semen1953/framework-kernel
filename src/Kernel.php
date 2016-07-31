<?php
declare(strict_types=1);

namespace Comely\Framework;

use Comely\Framework\Kernel\Client;
use Comely\Framework\Kernel\Constants;
use Comely\Framework\Kernel\DateTime;
use Comely\Framework\Kernel\ErrorHandler;
use Comely\Framework\Kernel\Security;
use Comely\Framework\Kernel\Traits\BootstrapTrait;
use Comely\Framework\Kernel\Traits\ConfigTrait;
use Comely\Framework\Kernel\Traits\DatabasesTrait;
use Comely\Framework\Kernel\Traits\InstancesTrait;
use Comely\Framework\Kernel\Traits\PropertiesTrait;
use Comely\IO\DependencyInjection\Container;
use Comely\IO\DependencyInjection\Repository;
use Comely\IO\Filesystem\Disk;

class Kernel implements Constants
{
    private $bootstrapped   =   false;
    private $client;
    private $container;
    private $dateTime;
    private $disks;
    private $errorHandler;
    private $security;

    use BootstrapTrait;
    use ConfigTrait;
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
                \Comely::baseClassName(
                    is_object($component) ? get_class($component) : $component
                ),
                $component
            );
        }

        // Set variables
        $this->dateTime =   new DateTime();
        $this->env  =   $env;
        $this->setRootPath(dirname(dirname(dirname(dirname(__DIR__)))));
        $this->errorHandler =   new ErrorHandler($this);

        // Setup disks instances
        $this->disks    =   new Repository();
        $this->container->add("disks", $this->disks);
        $this->disks->push(new Disk($this->rootPath . self::DS . self::CACHE_PATH), "cache");
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
        
        // Pre-config IO components
        if($this->container->has("Cipher")) {
            $this->cipher   =   $this->container->get("Cipher");
        }

        $this->loadConfig(); // Load configuration

        $this->bootstrapped =   true; // Declare bootstrapped
        $this->postBootstrap(); // Post bootstrap execution

        return $this;
    }

    /**
     * This method is executed AFTER kernel is bootstrapped
     */
    private function postBootstrap()
    {
        $this->security =   new Security($this); // Security
        $this->client   =  new Client(); // Client
    }

    /**
     * @param string $method
     * @param bool $boolOnFail
     * @return bool
     * @throws KernelException
     */
    public function isBootstrapped(string $method, bool $boolOnFail = false) : bool
    {
        if(!$this->bootstrapped) {
            if($boolOnFail  === true) {
                return false;
            }

            throw KernelException::notBootstrapped($method);
        }

        return true;
    }
}