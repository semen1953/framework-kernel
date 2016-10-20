<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\ErrorHandler;

use Comely\Framework\Kernel;
use Comely\IO\Toolkit\Arrays;
use Comely\Knit;

/**
 * Class Screen
 * @package Comely\Framework\Kernel\ErrorHandler
 */
class Screen
{
    /** @var Kernel */
    private $kernel;

    /**
     * Screen constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->kernel   =   $kernel;
    }

    /**
     * @param \Throwable $t
     * @throws \Comely\Framework\KernelException
     * @throws \Comely\KnitException
     */
    public function send(\Throwable $t)
    {
        $knit   =   (new Knit())
            ->setTemplatePath(__DIR__)
            ->setCompilerPath($this->kernel->getDisk("cache"));

        // Extract information from \Throwable
        $error  =   [
            "message"   =>  null,
            "method"    =>  null,
            "code"  =>  $t->getCode(),
            "file"  =>  $t->getFile(),
            "line"  =>  $t->getLine(),
            "trace" =>  []
        ];

        // Check if exception has "getTranslated" method
        $error["message"]   =   method_exists($t, "getTranslated") ? $t->getTranslated() : $t->getMessage();

        // Check if exception is child of ComelyException
        if(method_exists($t, "getMethod")   &&  is_subclass_of($t, "ComelyException")) {
            $error["method"]    =   $t->getMethod();
            $error["source"]    =   "Component";
        } else {
            $error["method"]    =   get_class($t);
            $error["source"]    =   "Caught";
        }

        // Populate Trace
        foreach($t->getTrace() as $trace) {
            if(Arrays::hasKeys($trace, ["function","file","line"])) {
                $trace["method"]    =   $trace["function"];
                if(isset($trace["class"])) {
                    $trace["method"]    =   $trace["class"] . $trace["type"] . $trace["function"];
                }

                $error["trace"][]   =   $trace;
            }
        }

        // Config
        $config =   $this->kernel->config()->getNode("app");
        $display    =   [
            "backtrace" =>  $config["errorHandler"]["screen"]["debugBacktrace"] ?? false,
            "triggered" =>  $config["errorHandler"]["screen"]["triggeredErrors"] ?? false,
            "paths" =>  $config["errorHandler"]["screen"]["completePaths"] ?? false
        ];

        // Assign values
        $knit->assign("display", $display);
        $knit->assign("error", $error);
        $knit->assign("triggered", $this->kernel->errorHandler()->fetchAll());
        $knit->assign("version", [
            "comely"    =>  \Comely::VERSION,
            "kernel"    =>  Kernel::VERSION,
            "framework" =>  Kernel::VERSION,
            "knit"  =>  Knit::VERSION
        ]);

        // Prepare template
        $screen =   $knit->prepare("screen.knit");
        $screen =   str_replace(
            "%%knit-timer%%",
            number_format($screen->getTimer(), 6, ".", ""),
            $screen->getOutput()
        );

        exit($screen);
    }
}