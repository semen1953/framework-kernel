<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits;

use Comely\Framework\Kernel\Security;
use Comely\Framework\KernelException;
use Comely\Framework\Kernel\DateTime;
use Comely\Framework\Kernel\ErrorHandler;
use Comely\IO\DependencyInjection\Container;
use Comely\IO\Filesystem\Disk;
use Comely\IO\i18n\Translator;
use Comely\IO\Security\Cipher;
use Comely\IO\Session\ComelySession;
use Comely\IO\Session\ComelySession\Proxy;
use Comely\IO\Session\Session;
use Comely\Knit;

/**
 * Class InstancesTrait
 * @package Comely\Framework\Kernel\Traits
 */
trait InstancesTrait
{
    private $cipher;
    private $session;
    private $translator;
    private $knit;

    /**
     * @return Container
     */
    public function getContainer() : Container
    {
        $this->isBootstrapped(__METHOD__);
        return $this->container;
    }

    /**
     * @param string $name
     * @return Disk
     */
    public function getDisk(string $name) : Disk
    {
        return $this->disks->pull($name);
    }

    /**
     * @return DateTime
     * @throws KernelException
     */
    public function dateTime() : DateTime
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->dateTime)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->dateTime;
    }

    /**
     * @return ErrorHandler
     * @throws KernelException
     */
    public function errorHandler() : ErrorHandler
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->errorHandler)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->errorHandler;
    }

    /**
     * @return Security
     * @throws KernelException
     */
    public function security() : Security
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->security)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->security;
    }

    /**
     * @return Cipher
     * @throws KernelException
     */
    public function getCipher() : Cipher
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->cipher)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->cipher;
    }

    /**
     * @return Proxy
     * @throws KernelException
     */
    public function getSession() : Proxy
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->session)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }
        
        return $this->session->getSession();
    }

    /**
     * @return Session
     * @throws KernelException
     */
    public function getSessionInstance() : Session
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->session)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->session;
    }

    /**
     * @return Translator
     * @throws KernelException
     */
    public function getTranslator() : Translator
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->translator)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->translator;
    }

    /**
     * @return Knit
     * @throws KernelException
     */
    public function getKnit() : Knit
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->knit)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }
        
        return $this->knit;
    }
}