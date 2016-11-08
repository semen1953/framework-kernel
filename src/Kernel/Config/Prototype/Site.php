<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Config\Prototype;


/**
 * Class Site
 * @package Comely\Framework\Kernel\Config\Prototype
 */
class Site
{
    /** @var null|string */
    public $title;
    /** @var null|string */
    public $domain;
    /** @var null|bool */
    public $https;
    /** @var null|integer */
    public $port;
    /** @var null|string */
    public $url;
}