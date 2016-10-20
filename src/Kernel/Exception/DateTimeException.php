<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Exception;

use Comely\Framework\KernelException;

/**
 * Class DateTimeException
 * @package Comely\Framework\Kernel\Exception
 */
class DateTimeException extends KernelException
{
    /** @var string */
    protected static $componentId =   "Comely\\Framework\\Kernel\\DateTime";

    /**
     * @param string $method
     * @param string $tz
     * @return DateTimeException
     */
    public static function badTimeZone(string $method, string $tz) : self
    {
        return new self($method, sprintf('Timezone "%1$s" is not valid', $tz), 2101);
    }
}