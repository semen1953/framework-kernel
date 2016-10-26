<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Exception;

use Comely\Framework\KernelException;

/**
 * Class BootstrapException
 * @package Comely\Framework\Kernel\Exception
 */
class BootstrapException extends KernelException
{
    /** @var string */
    protected static $componentId =   "Comely\\Framework\\Kernel\\Bootstrapper";

    /**
     * @return BootstrapException
     */
    public static function knitNode() : self
    {
        return new self(self::$componentId, '"knit" node must be set under "app" node', 2201);
    }

    /**
     * @return BootstrapException
     */
    public static function knitCompilerPath() : self
    {
        return new self(self::$componentId, '"compiler_path" must be set under "knit" node', 2202);
    }

    /**
     * @return BootstrapException
     */
    public static function sessionNode() : self
    {
        return new self(self::$componentId, '"sessions" node must be set under "app" node', 2211);
    }

    /**
     * @return BootstrapException
     */
    public static function sessionCache() : self
    {
        return new self(self::$componentId, 'Cache instance is not registered', 2212);
    }

    /**
     * @return BootstrapException
     */
    public static function sessionStorage() : self
    {
        return new self(
            self::$componentId,
            'Variable "storage_db" or "storage_path" must be set under "app.sessions" node',
            2213
        );
    }

    /**
     * @return BootstrapException
     */
    public static function cipherService() : self
    {
        return new self(self::$componentId, "Cipher instance not found in Kernel's services container", 2221);
    }

    /**
     * @return BootstrapException
     */
    public static function cacheNode() : self
    {
        return new self(self::$componentId, '"cache" node must be set under "app" node', 2241);
    }

    /**
     * @return BootstrapException
     */
    public static function cacheStatus() : self
    {
        return new self(self::$componentId, '"status" value must be on|off in "cache" node', 2242);
    }

    /**
     * @param string $engine
     * @return BootstrapException
     */
    public static function cacheEngine(string $engine) : self
    {
        return new self(self::$componentId, sprintf('"%1$s" is not a supported cache engine', $engine), 2243);
    }

    /**
     * @return BootstrapException
     */
    public static function mailerNode() : self
    {
        return new self(self::$componentId, '"mailer" node must be set under "app" node', 2251);
    }

    /**
     * @param string $agent
     * @return BootstrapException
     */
    public static function mailerAgent(string $agent) : self
    {
        return new self(self::$componentId, sprintf('"%1$s" is not a supported mailer agent', $agent), 2252);
    }
}