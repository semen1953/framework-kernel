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
    private $app;

    /**
     * AbstractJob constructor.
     * @param Kernel $app
     */
    public function __construct(Kernel $app)
    {
        $this->app  =   $app;
    }

    abstract public function run();
}