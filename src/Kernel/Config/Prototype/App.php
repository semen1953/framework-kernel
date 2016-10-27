<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Config\Prototype;

/**
 * Class App
 * @package Comely\Framework\Kernel\Config\Prototype
 */
class App
{
    /** @var null|string */
    public $timeZone;
    /** @var mixed */
    public $errorHandler;
    /** @var mixed */
    public $security;
    /** @var mixed */
    public $cache;
    /** @var mixed */
    public $knit;
    /** @var mixed */
    public $sessions;
    /** @var mixed */
    public $translations;
    /** @var mixed */
    public $mailer;
}