<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\App;

use Comely\Framework\KernelException;

/**
 * Class AppException
 * @package Comely\Framework\Kernel\App
 */
class AppException extends KernelException
{
    /**
     * AppException constructor.
     * @param string $message
     * @param int $code
     * @param int $previous
     */
    public function __construct(string $message, int $code = 0, $previous = null)
    {
        parent::__construct(__CLASS__, $message, $code, $previous);
    }
}