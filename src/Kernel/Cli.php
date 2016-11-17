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
        $job    =   array_key_exists(0, $args)  &&  !empty($args[0]) ? $args[0] : "console";
        unset($args[0]);
        $args   =   array_values($args);

        // If no job was specified, make necessary corrections
        if($job{0}  === "-") {
            array_unshift($args, $job);
            $job    =   "console";
        }

        // Starting Time
        $this->timeStamp    =   microtime(true);
        $this->success  =   false;

        // Check if Job class exists
        $this->job  =   sprintf('bin\\%s', strtolower($job));

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
                    $this->job  =   "bin\\help";
                    break;
            }
        } unset($arg, $flag, $value);
    }

    /**
     * @param array|null $components
     */
    public function bootstrap(array $components = null)
    {
        $this->banner();
        // Todo: Output buffering ON
        $this->headers();

        try {
            $this->app  =   new Kernel($components, $this->setup->environment);
            if($this->setup->cachedConfiguration) {
                $this->app->loadCachedConfig();
            }

            $this->app->bootstrap(); // Bootstrap Kernel
            $this->app->errorHandler()->setFormat('[%type|strtoupper%] %message% in %file% on line # %line%');

            // Auto-loader for bin/* files
            $rootPath   =   $this->app->rootPath();
            spl_autoload_register(function ($class) use($rootPath) {
                if(preg_match('/^bin\\\\[a-zA-Z0-9\_]+$/', $class)) {
                    $class  =   explode("\\", $class)[1] ?? null;
                    $path   =   sprintf('%1$s%3$sbin%3$s%2$s', $rootPath, $class, DIRECTORY_SEPARATOR);
                    if(@is_file($path)) {
                        /** @noinspection PhpIncludeInspection */
                        @include_once($path);
                    }
                }
            });

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
            VividShell::Repeat(".", 5, $this->sleep(150));
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
    public function sleep(int $ms = 0) : int
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
        VividShell::Print("Loading Job: ", $this->sleep(0), null, "");
        VividShell::Repeat(".", rand(5, 10), $this->sleep(150), "");
        if(!class_exists($jobClass)    ||  !is_a($jobClass, __NAMESPACE__ . "\\Cli\\AbstractJob", true)) {
            VividShell::Print(" {red}{b}{invert} %s {/}", $this->sleep(0), [$jobClass]);
            VividShell::Print("");
            throw CliException::jobNotFound($jobClass);
        }

        VividShell::Print(" {green}{b}{invert} %s {/}", $this->sleep(0), [$jobClass]);
        VividShell::Print("");

        $this->job  =   new $jobClass($this->app, $this);
        $this->job->run();
    }

    /**
     * Large COMELY banner
     */
    private function banner()
    {
        VividShell::Print("");
        VividShell::Print("{yellow}  ,ad8888ba,   ,ad8888ba,   88b           d88 88888888888 88     8b        d8  ");
        VividShell::Print('{yellow} d8"\'    `"8b d8"\'    `"8b  888b         d888 88          88      Y8,    ,8P   ');
        VividShell::Print('{yellow}d8\'          d8\'        `8b 88`8b       d8\'88 88          88       Y8,  ,8P  ');
        VividShell::Print('{yellow}88           88          88 88 `8b     d8\' 88 88aaaaa     88        "8aa8"    ');
        VividShell::Print('{yellow}88           88          88 88  `8b   d8\'  88 88"""""     88         `88\'      ');
        VividShell::Print('{yellow}Y8,          Y8,        ,8P 88   `8b d8\'   88 88          88          88       ');
        VividShell::Print('{yellow} Y8a.    .a8P Y8a.    .a8P  88    `888\'    88 88          88          88       ');
        VividShell::Print('{yellow}  `"Y8888Y"\'   `"Y8888Y"\'   88     `8\'     88 88888888888 88888888888 88       ');
        VividShell::Print("");
    }

    /**
     * Print headers
     */
    private function headers()
    {
        VividShell::Print("{magenta}{b}Comely IO{grey} v%s", $this->sleep(300), [\Comely::VERSION]);
        VividShell::Print("{magenta}{b}Framework Kernel{/} {gray}v%s", $this->sleep(300), [Kernel::VERSION]);
        VividShell::Print("");
    }

    /**
     * Introduce App
     */
    private function introduceApp()
    {
        // Loaded components
        //VividShell::Print("Includes: ", $this->sleep(100));
        foreach($this->app->getContainer()->list() as $component) {
            //VividShell::Print('{gray}├ {cyan}%s{/}', $this->sleep(150), [$component]);
            VividShell::Print('{cyan}%s{/}', $this->sleep(150), [$component]);
        }

        VividShell::Print("");

        // Application Title
        VividShell::Repeat("~", 5, $this->sleep(150));
        $lineNum   =   0;
        array_map(function ($line) use (&$lineNum) {
            $lineNum++;
            $eol    =   $lineNum   !== 2 ? PHP_EOL : "";
            VividShell::Print("{magenta}{b}{invert}" . $line, $this->sleep(0), null, $eol);
            if($lineNum === 2) {
                VividShell::Print(' {gray}v%s', $this->sleep(0), [@constant("App::VERSION") ?? "0.0.0"]);
            }
        }, VividShell\ASCII\Banners::Digital(@constant("App::NAME") ?? "Untitled App"));

        VividShell::Repeat("~", 5, $this->sleep(150));
        VividShell::Print("");
    }

    /**
     * Footer
     */
    private function footer()
    {
        // Completed
        VividShell::Print("");
        VividShell::Repeat("~", 5, $this->sleep(0));

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