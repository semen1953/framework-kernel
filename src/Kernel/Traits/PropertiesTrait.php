<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits;

use Comely\Framework\Kernel;
use Comely\Framework\KernelException;
use Comely\IO\Yaml\Yaml;

/**
 * Class PropertiesTrait
 * @package Comely\Framework\Kernel\Traits
 */
trait PropertiesTrait
{
    private $env;
    private $rootPath;
    private $timeStamps;

    /**
     * Sets root path
     *
     * @param string $path
     * @return Kernel
     * @throws KernelException
     */
    public function setRootPath(string $path) : Kernel
    {
        $path   =   rtrim($path, "\\/");
        if(!@is_dir($path)) {
            throw KernelException::badDirectoryPath(__METHOD__, $path);
        }

        $this->rootPath =   $path;
        Yaml::getParser()->setBaseDir($path);

        return $this;
    }

    /**
     * @return string
     */
    public function rootPath() : string
    {
        return $this->rootPath;
    }
}