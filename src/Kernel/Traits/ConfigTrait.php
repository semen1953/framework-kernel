<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Config;
use Comely\IO\Filesystem\Exception\DiskException;
use Comely\IO\Filesystem\Disk;

/**
 * Class ConfigTrait
 * @package Comely\Framework\Kernel\Traits
 */
trait ConfigTrait
{
    private $config;

    /**
     * Load compiled configuration from cache, if available
     * @return Kernel
     */
    public function loadCachedConfig() : Kernel
    {
        $configFile =   sprintf("bootstrap.config_%s.php.cache", $this->env);
        if(!isset($this->config)) {
            // Check if cached config file exists and is readable
            $cache   =   $this->disks->pull("cache");
            try {
                $config =   unserialize(
                    $cache->read($configFile)
                );
            } catch(DiskException $e) {
            }

            if(isset($config)   &&  $config instanceof Config) {
                // Configuration loaded from cache
                $this->config   =   $config;
            } else {
                // Load fresh configuration
                $this->readConfig();

                // Save to cache
                $cache->write(
                    $configFile,
                    serialize($this->config),
                    Disk::WRITE_FLOCK
                );
            }
        }

        return $this;
    }

    /**
     * Load fresh configuration, if not already loaded
     */
    private function readConfig()
    {
        if(!$this->config instanceof Config) {
            $configFile =   sprintf(
                '%2$s%1$s%3$s%1$sconfig_%4$s.yml',
                Kernel::DS,
                $this->rootPath,
                Kernel::CONFIG_PATH,
                $this->env
            );

            $this->config   =   new Config($configFile);
        }
    }

    /**
     * Load configuration to bootstrap Kernel
     */
    private function loadConfig()
    {
        // Read configuration if not already
        $this->readConfig();

        // Databases
        if(property_exists($this->config, "databases")) {
            // Database component defined in container?
            if($this->container->has("Database")) {
                $this->setDatabases($this->config->databases);
                unset($this->config->databases);
            }
        }

        // App
        if(property_exists($this->config, "app")) {
            // Security
            if(property_exists($this->config->app, "security")) {
                // Cipher Component
                if($this->container->has("Cipher")) {
                    $this->cipher   =   $this->container->get("Cipher");
                    // Cipher Key
                    if(
                        property_exists($this->config->app->security, "cipherKey")  &&
                        !empty($this->config->app->security->cipherKey)
                    ) {
                        $this->cipher->defaultSecret($this->config->app->security->cipherKey);
                    }

                    // Default hashing algorithm
                    if(
                        property_exists($this->config->app->security, "defaultHashAlgo")    &&
                        !empty($this->config->app->security->defaultHashAlgo)
                    ) {
                        $this->cipher->defaultHashAlgo($this->config->app->security->defaultHashAlgo);
                    }
                }

                // Remove security prop. from config->app
                unset($this->config->app->security);
            }

        }
    }

    /**
     * @return Config
     */
    public function config() : Config
    {
        return $this->config;
    }
}