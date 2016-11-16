<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Cli\AbstractJob;
use Comely\Framework\Kernel\Cli\Setup;
use Comely\Framework\Kernel\Exception\CliException;
use Comely\VividShell;

/**
 * Class Cli
 * @package Comely\Framework\Kernel
 */
class Cli
{
    /** @var Setup */
    private $setup;
    /** @var AbstractJob|string */
    private $job;
    /** @var Kernel */
    private $app;
    /** @var float */
    private $timeStamp;

    /**
     * Cli constructor.
     * @param array $args
     * @throws CliException
     */
    public function __construct(array $args)
    {
        // First argument is name of Job
        // Strip off value at index 0 and then reset array
        $job    =   $args[0] ?? "";
        unset($args[0]);
        $args   =   array_values($args);

        // Starting Time
        $this->timeStamp    =   microtime(true);

        // Check if Job class exists
        $this->job  =   sprintf('App\\Bin\\%s', \Comely::pascalCase($job));

        // CLI setup
        $this->setup    =   new Setup();
        foreach ($args as $arg) {
            $arg    =   explode("=", trim($arg));
            $flag   =   strtolower($arg[0]);
            $value  =   $arg[1];

            switch ($flag) {
                case "-e":
                case "-env":
                    $this->setup->environment   =   $value;
                    break;
                case "-f":
                case "-force":
                    $this->setup->force =   true;
                    break;
                case "-n":
                    $this->setup->notifyEmail   =   $value;
                    break;
                case "-d":
                case "-dump":
                    $this->setup->dumpFile  =   $value;
                    break;
                case "-nocache":
                    $this->setup->cachedConfiguration   =   false;
                    break;
                case "-h":
                case "--help":
                    $this->job  =   "App\\Bin\\Help";
                    break;
            }
        } unset($arg, $flag, $value);
    }

    /**
     * @param array|null $components
     */
    public function bootstrap(array $components = null)
    {
        // Todo: Output buffering ON
        $this->headers();

        try {
            $this->app  =   new Kernel($components, $this->setup->environment);
            if($this->setup->cachedConfiguration) {
                $this->app->loadCachedConfig();
            }

            $this->app->bootstrap(); // Bootstrap Kernel
            $this->introduceApp(); // Introduce App
            $this->run();
        } catch (\Throwable $t) {
            VividShell::Print(
                '{red}%1$s:{/} %2$s {gray}in{/i} %3$s {gray}on line{/i} %4$s',
                300,
                [
                    $t instanceof \Exception ? get_class($t) : "Fatal Error",
                    $t->getMessage(),
                    $t->getFile(),
                    $t->getLine()
                ]
            );

            VividShell::Print("");
            VividShell::Print("Debug Backtrace");
            VividShell::Loading(".", 5, 150);
            VividShell::Print("");

            print $t->getTraceAsString();
            VividShell::Print("");
        }

        $this->footer();

        // Todo: Output buffering OFF
        // Todo: E-mail notification
        // Todo: File Dump
    }

    /**
     * Run a job
     *
     * @throws CliException
     * @throws \Exception
     */
    private function run()
    {
        $jobClass   =   (string) $this->job;
        if(!class_exists($jobClass)    ||  !is_a($jobClass, __NAMESPACE__ . "\\AbstractJob", true)) {
            throw CliException::jobNotFound($jobClass);
        }

        VividShell::Print("Loading Job: {cyan}%s{/}", 100, [$jobClass], "");
        VividShell::Loading(".", 6, 150);
        VividShell::Print("");

        $this->job  =   new $jobClass($this->app);
        $this->job->run();
    }

    /**
     * Print headers
     */
    private function headers()
    {
        VividShell::Print("{magenta}{b}Comely Framework Kernel {/}{grey}v%s", 300, [Kernel::VERSION]);
        VividShell::Print("{yellow}Comely IO {grey}v%s", 300, [\Comely::VERSION]);
        VividShell::Print("");
        VividShell::Print("Loading app", 0, null, "");
        VividShell::Loading(".", 10, 100);
        VividShell::Print("");
    }

    /**
     * Introduce App
     */
    private function introduceApp()
    {
        // Application Title
        try {
            $appNode    =   $this->app->config()->getNode("app");
        } catch (\Exception $e) {
            $appNode    =   [];
        }

        VividShell::Print(
            '{green}%1$s{/} {gray}v%2$s{/}',
            300,
            [
                $appNode["name"] ?? "Unknown App",
                $appNode["version"] ?? "0.0.0"
            ]
        );
        VividShell::Print("");
    }

    /**
     * Footer
     */
    private function footer()
    {
        VividShell::Print("");
        VividShell::Print(
            "{gray}Execution Time:{/} %s",
            0,
            [
                number_format((microtime(true)-$this->timeStamp), 8)
            ]
        );
        VividShell::Print(
            "{gray}Memory Usage:{/} %sMB of %sMB",
            0,
            [
                number_format((memory_get_usage(false)/1024)/1024, 8, ".", ","),
                number_format((memory_get_usage(true)/1024)/1024, 8, ".", ",")
            ]
        );
    }
}