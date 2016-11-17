<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Cli;

use Comely\Framework\Kernel;

/**
 * Class AbstractJob
 * @package Comely\Framework\Kernel\Cli
 */
abstract class AbstractJob
{
    /** @var Kernel */
    protected $app;
    /** @var Kernel\Cli */
    protected $cli;

    /**
     * AbstractJob constructor.
     * @param Kernel $app
     * @param Kernel\Cli $cli
     */
    final public function __construct(Kernel $app, Kernel\Cli $cli)
    {
        $this->app  =   $app;
        $this->cli  =   $cli;
    }

    abstract public function run();
}