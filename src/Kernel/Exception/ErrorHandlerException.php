<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Exception;

use Comely\Framework\KernelException;

/**
 * Class ErrorHandlerException
 * @package Comely\Framework\Kernel\Exception
 */
class ErrorHandlerException extends KernelException
{
    /** @var string */
    protected static $componentId   =   "Comely\\Framework\\Kernel\\ErrorHandler";

    /**
     * @param string $method
     * @return ErrorHandlerException
     */
    public static function badFlag(string $method) : self
    {
        return new self($method, "Inappropriate flag for error handling", 2151);
    }
}