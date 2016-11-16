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
    /** @var bool */
    private $success;

    /**
     * Cli constructor.
     * @param array $args
     * @throws CliException
     */
    public function __construct(array $args)
    {
        // First argument is name of Job
        // Strip off value at index 0 and then reset array
        $job    =   array_key_exists(0, $args)  &&  !empty($args[0]) ? $args[0] : "default";
        unset($args[0]);
        $args   =   array_values($args);

        // If no job was specified, make necessary corrections
        if($job{0}  === "-") {
            array_unshift($args, $job);
            $job    =   "default";
        }

        // Starting Time
        $this->timeStamp    =   microtime(true);
        $this->success  =   false;

        // Check if Job class exists
        $this->job  =   sprintf('App\\Bin\\%s', \Comely::pascalCase($job));

        // CLI setup
        $this->setup    =   new Setup();
        foreach ($args as $arg) {
            $arg    =   explode("=", trim($arg));
            $flag   =   strtolower($arg[0]);
            $value  =   $arg[1] ?? null;

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
                case "-b":
                    $this->setup->noSleep   =   true;
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
            $this->app->errorHandler()->setFormat('[%type|strtoupper%] %message% in %file% on line # %line%');

            $this->introduceApp(); // Introduce App
            $this->run();
            $this->success  =   true;
        } catch (\Throwable $t) {
            VividShell::Print(
                '{red}%1$s:{/} %2$s',
                $this->sleep(200),
                [
                    $t instanceof \Exception ? get_class($t) : "Fatal Error",
                    $t->getMessage()
                ]
            );

            VividShell::Print("{yellow}File:{/} %s", 0, [$t->getFile()]);
            VividShell::Print("{yellow}Line:{/} {cyan}%d", 0, [$t->getLine()]);
            VividShell::Print("");
            VividShell::Print("Debug Backtrace");
            VividShell::Loading(".", 5, $this->sleep(150));
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
     * @param int $ms
     * @return int
     */
    private function sleep(int $ms = 0) : int
    {
        return $this->setup->noSleep ? 0 : $ms;
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
        if(!$this->setup->noSleep) {

        }
        VividShell::Print("Loading Job", $this->sleep(0), null, "");
        VividShell::Loading(".", 5, $this->sleep(150), "");

        if(!class_exists($jobClass)    ||  !is_a($jobClass, __NAMESPACE__ . "\\Cli\\AbstractJob", true)) {
            VividShell::Print(" {red}%s{/}", $this->sleep(0), [$jobClass]);
            VividShell::Print("");
            throw CliException::jobNotFound($jobClass);
        }

        VividShell::Print(" {cyan}%s{/}", $this->sleep(0), [$jobClass]);
        VividShell::Print("");

        $this->job  =   new $jobClass($this->app);
        $this->job->run();
    }

    /**
     * Print headers
     */
    private function headers()
    {
        VividShell::Print(
            "{magenta}{b}{invert}Comely Framework Kernel{/} {grey}v%s",
            $this->sleep(300),
            [
                Kernel::VERSION
            ]
        );
        VividShell::Print("{yellow}Comely IO {grey}v%s", $this->sleep(300), [\Comely::VERSION]);
        VividShell::Print("");

        if(!$this->setup->noSleep) {
            VividShell::Print("Loading app", $this->sleep(0), null, "");
            VividShell::Loading(".", 10, $this->sleep(100));
            VividShell::Print("");
        }
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

        VividShell::Loading("~", 5, $this->sleep(0));
        VividShell::Print(
            '{magenta}%1$s{/} {gray}v%2$s{/}',
            $this->sleep(0),
            [
                $appNode["name"] ?? "Unknown App",
                $appNode["version"] ?? "0.0.0"
            ]
        );
        VividShell::Loading("~", 5, $this->sleep(0));
        VividShell::Print("");
    }

    /**
     * Footer
     */
    private function footer()
    {
        // Completed
        VividShell::Print("");
        VividShell::Print("");
        VividShell::Loading("~", 5, $this->sleep(0));

        // Errors
        $errors =   $this->app->errorHandler()->fetchAll();
        if(count($errors)   <=  0) {
            if($this->success) {
                VividShell::Print("{green}{b}Completed!{/}", $this->sleep(100));
                VividShell::Print("{gray}%d Triggered Errors", $this->sleep(0), [count($errors)]);
            }
        } else {
            VividShell::Print("{red}Warning", $this->sleep(100));
            VividShell::Print("{yellow}%d Triggered Errors", $this->sleep(0), [count($errors)]);
            VividShell::Print("");

            foreach($errors as $error) {
                VividShell::Print(
                    $error["formatted"] ?? $error["message"] ?? "",
                    $this->sleep(100)
                );
            }
        }

        // Footprint
        VividShell::Print("");
        VividShell::Print(
            "Execution Time: {gray}%s seconds{/}",
            $this->sleep(0),
            [
                number_format((microtime(true)-$this->timeStamp), 4)
            ]
        );
        VividShell::Print(
            "Memory Usage: {gray}%sMB{/} of {gray}%sMB{/}",
            $this->sleep(0),
            [
                round((memory_get_usage(false)/1024)/1024, 2),
                round((memory_get_usage(true)/1024)/1024, 2)
            ]
        );
    }
}