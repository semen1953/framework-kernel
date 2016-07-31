<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

/**
 * Class Client
 * @package Comely\Framework\Kernel
 */
class Client
{
    public $https;
    public $ipAddress;
    public $origin;
    public $port;
    public $userAgent;
    
    public function __construct()
    {
        $this->https    =   $_SERVER["HTTPS"] ? true : false;
        $this->ipAddress    =   $_SERVER["HTTP_CF_CONNECTING_IP"] ?? $_SERVER["REMOTE_ADDR"] ?? "";
        $this->origin   =   $_SERVER["HTTP_REFERER"] ?? "";
        $this->port =   $_SERVER["REMOTE_PORT"] ?? 0;
        $this->userAgent    =   $_SERVER["HTTP_USER_AGENT"] ?? "";
    }
}