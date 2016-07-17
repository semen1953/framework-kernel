<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\IO\DependencyInjection\Container;

/**
 * Class InstancesTrait
 * @package Comely\Framework\Kernel
 */
trait InstancesTrait
{
    private $cipher;
    private $config;
    
    /**
     * @return Container
     */
    public function getContainer() : Container
    {
        return $this->container;
    }

    /**
     * @return DateTime
     */
    public function dateTime() : DateTime
    {
        return $this->dateTime;
    }

    /**
     * @return ErrorHandler
     */
    public function errorHandler() : ErrorHandler
    {
        return $this->errorHandler;
    }
}