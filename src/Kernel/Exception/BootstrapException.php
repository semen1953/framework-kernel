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
    protected static $componentId =   "Comely\\Framework\\Kernel\\Bootstrap";

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
    public static function sessionStorage() : self
    {
        return new self(
            self::$componentId,
            'Variable "storage_db" or "storage_path" must be set under "app.sessions" node',
            2212
        );
    }

    /**
     * @return BootstrapException
     */
    public static function cipherService() : self
    {
        return new self(self::$componentId, 'Cipher instance not found in Kernel\'s services container', 2221);
    }


}