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

    /**
     * Difference in two different timestamps in seconds
     *
     * @param int $stamp1
     * @param int|null $stamp2
     * @return int
     */
    public function difference(int $stamp1, int $stamp2 = null) : int
    {
        if(empty($stamp2)) {
            $stamp2 =   time();
        }

        return $stamp2 > $stamp1 ?  $stamp2-$stamp1 : $stamp1-$stamp2;
    }

    /**
     * @param int $stamp1
     * @param int|null $stamp2
     * @return float
     */
    public function minutesDifference(int $stamp1, int $stamp2 = null) : float
    {
        return round($this->difference($stamp1, $stamp2)/60, 1);
    }

    /**
     * @param int $stamp1
     * @param int|null $stamp2
     * @return float
     */
    public function hoursDifference(int $stamp1, int $stamp2 = null) : float
    {
        return round(($this->difference($stamp1, $stamp2)/60)/60, 1);
    }
}