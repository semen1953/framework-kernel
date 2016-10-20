<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Exception;

use Comely\Framework\KernelException;

/**
 * Class HttpException
 * @package Comely\Framework\Kernel\Exception
 */
class HttpException extends KernelException
{
    /** @var string */
    protected static $componentId   =   "Comely\\Framework\\Kernel\\Http";
}