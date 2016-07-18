<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits;

use Comely\IO\DependencyInjection\Container;
use Comely\Framework\Kernel\DateTime;
use Comely\Framework\Kernel\ErrorHandler;
use Comely\IO\Security\Cipher;

/**
 * Class InstancesTrait
 * @package Comely\Framework\Kernel\Traits
 */
trait InstancesTrait
{
    private $cipher;

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

    /**
     * @return Cipher
     */
    public function getCipher() : Cipher
    {
        return $this->cipher;
    }
}