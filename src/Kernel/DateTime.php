<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel\Exception\DateTimeException;

/**
 * Class DateTime
 * @package Comely\Framework\Kernel
 */
class DateTime
{
    /** @var string */
    private $timeZone;

    /**
     * Sets default timezone
     *
     * @param string $zone
     * @throws DateTimeException
     */
    public function setTimeZone(string $zone)
    {
        $zones	=	\DateTimeZone::listIdentifiers();
        if(!in_array($zone, $zones)) {
            throw DateTimeException::badTimeZone(__METHOD__, $zone);
        }

        $this->timeZone =   $zone;
        date_default_timezone_set($zone);
    }

    /**
     * Gets current timezone
     * @return string
     */
    public function getTimeZone() : string
    {
        return $this->timeZone;
    }
}