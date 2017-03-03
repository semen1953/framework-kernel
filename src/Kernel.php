<?php
declare(strict_types=1);

namespace Comely\Framework;

use Comely\Framework\Kernel\Bootstrapper;
use Comely\Framework\Kernel\Client;
use Comely\Framework\Kernel\Config;
use Comely\Framework\Kernel\DateTime;
use Comely\Framework\Kernel\ErrorHandler;
use Comely\Framework\Kernel\Memory;
use Comely\Framework\Kernel\PlainObject;
use Comely\Framework\Kernel\Security;
use Comely\IO\Cache\Cache;
use Comely\IO\Database\Database;
use Comely\IO\Database\Schema;
use Comely\IO\DependencyInjection\Container;
use Comely\IO\DependencyInjection\Repository;
use Comely\IO\Emails\Mailer;
use Comely\IO\Filesystem\Disk;
use Comely\IO\Filesystem\Exception\DiskException;
use Comely\IO\i18n\Translator;
use Comely\IO\Security\Cipher;
use Comely\IO\Session\ComelySession;
use Comely\IO\Session\Session;
use Comely\IO\Yaml\Yaml;
use Comely\Knit;

/**
 * Class Kernel
 * @package Comely\Framework
 */
class Kernel extends Bootstrapper
{
    /** @var bool */
    protected $bootstrapped   =   false;
    /** @var Client */
    protected $client;
    /** @var DateTime */
    protected $dateTime;
    /** @var ErrorHandler */
    protected $errorHandler;
    /** @var Security */
    protected $security;
    /** @var string */
    protected $env;
    /** @var array */
    protected $databases;
    /** @var Memory */
    protected $memory;

    /**
     * Framework Kernel constructor.
     *
     * @param array $components
     * @param string $env
     */
    public function __construct(array $components, string $env)
    {
        // Create a private dependency injection container
        $this->container    =   new Container();

        // Run through all passed components
        foreach($components as $component)
        {
            /// Add to container
            $this->container->add(
                \Comely::baseClassName(
                    is_object($component) ? get_class($component) : $component
                ),
                $component
            );
        }

        // Set variables
        $this->dateTime =   new DateTime();
        $this->env  =   $env;
        $this->setRootPath(dirname(dirname(dirname(dirname(__DIR__)))));
        $this->errorHandler =   new ErrorHandler($this);

        // Setup disks instances
        $this->disks    =   new Repository();
        $this->container->add("disks", $this->disks);
        $this->disks->push(new Disk($this->rootPath . self::DS . self::CACHE_PATH), "cache");
    }

    /**
     * @return Kernel
     * @throws KernelException
     */
    public function bootstrap() : self
    {
        if($this->bootstrapped) {
            // Already bootstrapped?
            throw KernelException::bootstrapped();
        }
        
        // Pre-config IO components
        if($this->container->has("Cipher")) {
            $this->cipher   =   $this->container->get("Cipher");
        }

        $this->loadConfig(); // Load configuration

        $this->bootstrapped =   true; // Declare bootstrapped
        $this->client   =   new Client(); // Client
        $this->memory   =   Memory::getInstance(); // Memory

        // Security component requires Session component
        if(isset($this->session)) {
            $this->security =   new Security($this); // Security
        }

        // Set cache instance in memory
        if(isset($this->cache)) {
            $this->memory->setCache($this->cache);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param bool $boolOnFail
     * @return bool
     * @throws KernelException
     */
    public function isBootstrapped(string $method, bool $boolOnFail = false) : bool
    {
        if(!$this->bootstrapped) {
            if($boolOnFail  === true) {
                return false;
            }

            throw KernelException::notBootstrapped($method);
        }

        return true;
    }

    /**
     * Config. Methods
     */

    /**
     * Load compiled configuration from cache, if available
     * @return Kernel
     * @throws KernelException
     */
    public function loadCachedConfig() : Kernel
    {
        if($this->bootstrapped) {
            // Already bootstrapped?
            throw KernelException::bootstrapped();
        }

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
            }

            // Fluent/ORM callback args
            Schema::setCallbackArgs($this);

            // Remove databases node from config
            unset($this->config->databases);
        }

        // Site
        if(property_exists($this->config, "site")) {
            // Build a URL from given props.
            if(!property_exists($this->config->site, "url")) {
                $domain =   $this->config->site->domain ?? null;
                $https  =   $this->config->site->https ?? null;
                $port   =   intval($this->config->site->port ?? 0);

                if(!empty($domain)) {
                    $this->config->site->url    =   sprintf(
                        '%s://%s%s/',
                        ($https  === true) ? "https" : "http",
                        $domain,
                        ($port > 0) ? sprintf(':%d', $port) : ''
                    );
                }
            }
        }

        // App
        if(property_exists($this->config, "app")) {
            // Timezone
            if(property_exists($this->config->app, "timeZone")) {
                $this->dateTime->setTimeZone($this->config->app->timeZone);
            }

            // Error Handler
            if(property_exists($this->config->app, "errorHandler")) {
                // Format
                if(property_exists($this->config->app->errorHandler, "format")) {
                    $this->errorHandler->setFormat($this->config->app->errorHandler->format);
                }

                // Flag for handling triggered error messages
                $this->errorHandler->setFlag(Kernel::ERRORS_COLLECT);
                if(property_exists($this->config->app->errorHandler, "hideErrors")) {
                    if(!$this->config->app->errorHandler->hideErrors) {
                        $this->errorHandler->setFlag(Kernel::ERRORS_DEFAULT);
                    }
                }
            }

            // Security
            if(property_exists($this->config->app, "security")) {
                // Cipher Component
                if(isset($this->cipher)) {
                    // Configure Cipher
                    $this->registerCipher();
                }

                // Remove security prop. from config->app
                unset($this->config->app->security);
            }

            // Mailer
            if($this->container->has("Mailer")) {
                // Register Mailer
                $this->registerMailer();
            }

            // Cache
            if($this->container->has("Cache")) {
                // Register Cache
                $this->registerCache();
            }

            // Sessions
            if($this->container->has("Session")) {
                // Register Session
                $this->registerSession();
            }

            // Translator
            if($this->container->has("Translator")) {
                $this->registerTranslator();
            }

            // Knit
            if($this->container->has("Knit")) {
                $this->registerKnit();
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

    /**
     * Properties
     */

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

    /**
     * @param string $lang
     * @return bool
     */
    public function setTranslatorLanguage(string $lang) : bool
    {
        $this->isBootstrapped(__METHOD__);
        if(!$this->hasTranslator()) {
            trigger_error('Translator component is not registered', E_USER_WARNING);
            return false;
        }

        $cache  =   false;
        if($this->config->app->translations->cache  === true) {
            $cache  =   true;
        }

        try {
            $language   =   $cache  === true ?
                $this->getCachedLanguage($lang) : $this->translator->language($lang);
            $this->translator->bindLanguage($language);
            $this->getSession()
                ->getBags()
                ->getBag("Comely")
                ->getBag("Framework")
                ->set("language", $lang);

            return true;
        } catch (\Exception $e) {
            trigger_error(
                sprintf(
                    '%1$s: [%2$s][%3$d] %4$s',
                    __METHOD__,
                    get_class($e),
                    $e->getCode(),
                    $e->getMessage()
                ),
                E_USER_WARNING
            );
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function getTranslatorLanguage()
    {
        $this->isBootstrapped(__METHOD__);
        if(!$this->hasTranslator()) {
            trigger_error('Translator component is not registered', E_USER_WARNING);
            return false;
        }

        return $this->translator->getBoundLanguage()->name();
    }

    /**
     * Instances
     */

    /**
     * @return Container
     */
    public function getContainer() : Container
    {
        $this->isBootstrapped(__METHOD__);
        return $this->container;
    }

    /**
     * @param string $name
     * @return Disk
     */
    public function getDisk(string $name) : Disk
    {
        return $this->disks->pull($name);
    }

    /**
     * @return DateTime
     * @throws KernelException
     */
    public function dateTime() : DateTime
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->dateTime)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->dateTime;
    }

    /**
     * @return ErrorHandler
     * @throws KernelException
     */
    public function errorHandler() : ErrorHandler
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->errorHandler)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->errorHandler;
    }

    /**
     * @return Security
     * @throws KernelException
     */
    public function security() : Security
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->security)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->security;
    }

    /**
     * @return Client
     * @throws KernelException
     */
    public function client() : Client
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->client)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->client;
    }

    /**
     * @return Memory
     */
    public function memory() : Memory
    {
        return $this->memory();
    }

    /**
     * @return Cache
     * @throws KernelException
     */
    public function getCache() : Cache
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->cache)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }


        return $this->cache;
    }

    /**
     * @return bool
     */
    public function hasCache() : bool
    {
        return $this->cache instanceof Cache ? true : false;
    }

    /**
     * @return Mailer
     * @throws KernelException
     */
    public function getMailer() : Mailer
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->mailer)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->mailer;
    }

    /**
     * @return bool
     */
    public function hasMailer() : bool
    {
        return $this->mailer instanceof Mailer ? true : false;
    }

    /**
     * @return Cipher
     * @throws KernelException
     */
    public function getCipher() : Cipher
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->cipher)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->cipher;
    }

    /**
     * @return bool
     */
    public function hasCipher() : bool
    {
        return $this->cipher instanceof Cipher ? true : false;
    }

    /**
     * @return ComelySession
     * @throws KernelException
     */
    public function getSession() : ComelySession
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->session)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->session->getSession();
    }

    /**
     * @return bool
     */
    public function hasSession() : bool
    {
        return $this->session instanceof Session ? true : false;
    }

    /**
     * @return Session
     * @throws KernelException
     */
    public function getSessionInstance() : Session
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->session)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->session;
    }

    /**
     * @return Translator
     * @throws KernelException
     */
    public function getTranslator() : Translator
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->translator)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->translator;
    }

    /**
     * @return bool
     */
    public function hasTranslator() : bool
    {
        return $this->translator instanceof Translator ? true : false;
    }

    /**
     * @return Knit
     * @throws KernelException
     */
    public function getKnit() : Knit
    {
        $this->isBootstrapped(__METHOD__);
        if(!isset($this->knit)) {
            throw KernelException::instanceNotAvailable(__METHOD__);
        }

        return $this->knit;
    }

    /**
     * @return bool
     */
    public function hasKnit() : bool
    {
        return $this->knit instanceof Knit ? true : false;
    }

    /**
     * Databases
     */

    /**
     * Save database credentials
     *
     * Credentials are initially stored as associative arrays which are replaced with instances Database upon
     * first call to getDb() method
     *
     * @param PlainObject $dbs
     * @throws KernelException
     */
    private function setDatabases(PlainObject $dbs)
    {
        $dbs    =   json_decode(json_encode($dbs), true);
        $requiredKeys   =   ["driver", "host", "username", "password", "name"];

        $this->databases    =   [];
        foreach($dbs as $id => $credentials) {
            $id =   (is_string($id)) ? $id : strval($id);
            if(!is_array($credentials)) {
                // Credentials must be in an associative array
                throw KernelException::badDbCredentials($id);
            }

            foreach($requiredKeys as $required) {
                if(!array_key_exists($required, $credentials)) {
                    // A required key is missing
                    throw KernelException::badDbCredentials($id, $required);
                }

                // Convert NULL types to empty strings
                if(is_null($credentials[$required])) {
                    $credentials[$required] =   "";
                }
            }

            // Save credentials for time being
            $this->databases[$id]   =   $credentials;
        }
    }

    /**
     * @param string|null $id
     * @return Database
     * @throws KernelException
     */
    public function getDb(string $id = null) : Database
    {
        $this->isBootstrapped(__METHOD__);

        if(!isset($id)) {
            // No reference ID provided, fetch first database
            $db =   reset($this->databases);
            $id =   key($this->databases);
            if(!$db ||  empty($id)) {
                // No databases were defined
                throw KernelException::dbNotFound("");
            }
        } else {
            if(!array_key_exists($id, $this->databases)) {
                // Database not found
                throw KernelException::dbNotFound($id);
            }

            $db =   $this->databases[$id];
        }

        // Check if database instance was created
        if(is_array($db)) {
            // Check for custom port
            if(!empty($db["port"])) {
                $db["host"] .=  ":" . $db["port"];
            }

            // SQLite Databases
            if($db["driver"]    === "sqlite") {
                // Prepend root path
                $db["name"] =   $this->rootPath . self::DS . $db["name"];
            }

            // Create instance on first call
            $persistent =   (array_key_exists("persistent", $db)    &&  $db["persistent"]   === true) ? true : false;
            $db =   new Database(
                $db["driver"],
                $db["name"],
                $db["host"],
                $db["username"],
                $db["password"],
                $persistent
            );

            // Override with instance of Database
            $this->databases[$id]   =   $db;
        }

        // Return instance of Comely\IO\Database\Database
        return $db;
    }
}