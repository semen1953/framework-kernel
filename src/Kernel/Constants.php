<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

/**
 * Interface Constants
 * @package Comely\Framework\Kernel
 */
interface Constants
{
    const VERSION   =   "1.0.1-beta";

    const DS    =   DIRECTORY_SEPARATOR;
    const EOL   =   PHP_EOL;
    
    const CONFIG_PATH   =   "app/config";
    const CACHE_PATH    =   "tmp/cache";

    const ERRORS_DEFAULT    =   1;
    const ERRORS_COLLECT    =   2;
    const ERRORS_PUBLISH    =   4;
    const ERRORS_REPORT_ALL =   8;
    const ERRORS_IGNORE_NOTICE  =   16;
}