<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Exception;

use Comely\Framework\KernelException;

/**
 * Class CliException
 * @package Comely\Framework\Kernel\Exception
 */
class CliException extends KernelException
{
    /** @var string */
    public static $componentId  =   "Comely\\Framework\\Kernel\\Cli";

    /**
     * @param string $job
     * @return CliException
     */
    public static function jobNotFound(string $job) : self
    {
        return new self(self::$componentId, sprintf('Job "%1$s" not found', $job), 2301);
    }
}