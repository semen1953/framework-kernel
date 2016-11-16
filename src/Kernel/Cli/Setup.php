<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Cli;

/**
 * Class Setup
 * @package Comely\Framework\Kernel\Cli
 */
class Setup
{
    /** @var string */
    public $environment;
    /** @var bool */
    public $force;
    /** @var null|string */
    public $notifyEmail;
    /** @var null|string */
    public $dumpFile;
    /** @var bool */
    public $cachedConfiguration;
    /** @var bool */
    public $noSleep;

    /**
     * Setup constructor.
     */
    public function __construct()
    {
        $this->environment  =   "dev";
        $this->force    =   false;
        $this->cachedConfiguration  =   true;
        $this->noSleep  =   false;
    }
}