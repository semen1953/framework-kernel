<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Exception\BootstrapException;
use Comely\IO\DependencyInjection\Container;
use Comely\IO\DependencyInjection\Repository;
use Comely\IO\Filesystem\Disk;
use Comely\IO\Filesystem\Exception\DiskException;
use Comely\IO\i18n\Translator;
use Comely\IO\Security\Cipher;
use Comely\IO\Session\Session;
use Comely\IO\Toolkit\Time;
use Comely\Knit;

/**
 * Class Bootstrapper
 * @package Comely\Framework\Kernel
 */
abstract class Bootstrapper implements Constants
{
    /** @var Container */
    protected $container;
    /** @var Config */
    protected $config;
    /** @var Cipher */
    protected $cipher;
    /** @var Session */
    protected $session;
    /** @var Translator */
    protected $translator;
    /** @var Knit */
    protected $knit;
    /** @var string|null */
    protected $rootPath;
    /** @var Repository */
    protected $disks;

    /**
     * Register Cipher Component
     */
    protected function registerCipher()
    {
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

    /**
     * Register Knit Component
     * @throws BootstrapException
     */
    protected function registerKnit()
    {
        // Container has Knit component, must be defined in config
        if(!property_exists($this->config->app, "knit")) {
            throw BootstrapException::knitNode();
        }

        // Bootstrap knit
        $this->knit =   $this->container->get("Knit");

        // Compiler path
        if(!property_exists($this->config->app->knit, "compilerPath")) {
            throw BootstrapException::knitCompilerPath();
        }

        try {
            $this->knit->setCompilerPath($this->rootPath . self::DS . $this->config->app->knit->compilerPath);

            // Caching
            if(property_exists($this->config->app->knit, "caching")) {
                /** @var $this Kernel */
                switch ($this->config->app->knit->caching)
                {
                    case "static":
                    case 1:
                        $this->knit->setCachePath($this->getDisk("cache"))
                            ->setCaching(Knit::CACHE_STATIC);
                        break;
                    case "dynamic":
                    case 2:
                        $this->knit->setCachePath($this->getDisk("cache"))
                            ->setCaching(Knit::CACHE_DYNAMIC);
                        break;
                }
            }
        } catch(\ComelyException $e) {
            throw new BootstrapException(__METHOD__, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register Session Component
     * @throws BootstrapException
     */
    protected function registerSession()
    {
        // Container has Session component, must be defined in config.
        if(!property_exists($this->config->app, "sessions")) {
            throw BootstrapException::sessionNode();
        }

        // Means of storage must be defined
        try {
            /** @var $this Kernel */
            if(property_exists($this->config->app->sessions, "storageDb")) {
                $storage    =   $this->getDb($this->config->app->sessions->storageDb);
            } elseif(property_exists($this->config->app->sessions, "storagePath")) {
                $storage    =   new Disk(
                    $this->rootPath . self::DS . $this->config->app->sessions->storagePath
                );
            } else {
                // No storage configuration was set
                throw BootstrapException::sessionStorage();
            }

            // Retrieve session instance
            $this->session  =   $this->container->get("Session", $storage);

            // Session configuration
            // Expiry
            if(property_exists($this->config->app->sessions, "expire")) {
                $this->session->setSessionLife(Time::unitsToSeconds($this->config->app->sessions->expire));
            }

            // Encryption
            if(property_exists($this->config->app->sessions, "encrypt")) {
                if($this->config->app->sessions->encrypt    === true) {
                    if(!isset($this->cipher)) {
                        throw BootstrapException::cipherService();
                    }

                    $this->session->useCipher($this->cipher);
                }
            }

            // Cookie
            $cookie =   [false, "30d", "", "", false, true];
            if(property_exists($this->config->app->sessions, "cookie")) {
                $cookie[0]  =   true;
                $cookieArgCount =   1;
                foreach(["expire","path","domain","secure","httpOnly"] as $cookieArg) {
                    if(property_exists($this->config->app->sessions->cookie, $cookieArg)) {
                        $cookie[$cookieArgCount] =   $this->config->app->sessions->cookie->$cookieArg;
                    }

                    $cookieArgCount++;
                }
            }

            $cookie[1]  =   Time::unitsToSeconds($cookie[1]);
            call_user_func_array([$this->session,"setCookie"], $cookie);

            // PBKDF2 Hashing
            // Salt
            if(property_exists($this->config->app->sessions, "hashSalt")) {
                $this->session->setHashSalt(strval($this->config->app->sessions->hashSalt));
            }

            // Cost
            if(property_exists($this->config->app->sessions, "hashCost")) {
                $this->session->setHashCost(intval($this->config->app->sessions->hashCost));
            }

            // Bootstrap Session
            $this->session->start();

            // If storage is filesystem, save instance in disks repo.
            if($storage instanceof Disk) {
                $this->disks->push($storage, "sessions");
            }
        } catch(\ComelyException $e) {
            throw new BootstrapException(__METHOD__, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register Translator (i18n) Component
     * @throws BootstrapException
     */
    protected function registerTranslator()
    {
        if(!property_exists($this->config->app, "translations")) {
            throw new BootstrapException(__METHOD__, '"translations" node must be set under "app" node', 2231);
        }

        if(!property_exists($this->config->app->translations, "path")) {
            throw new BootstrapException(
                __METHOD__,
                'Var "path" to translations files must be set under "app.translations" node',
                2232
            );
        }

        if(!property_exists($this->config->app->translations, "fallBack")) {
            throw new BootstrapException(
                __METHOD__,
                'Var "fall_back" language must be set under "app.translations" node',
                2233
            );
        }

        if(!isset($this->session)) {
            throw new BootstrapException(__METHOD__, '"Translator" requires "Session" component', 2234);
        }

        // Cache of compiled language files?
        $cache  =   false;
        if(
            property_exists($this->config->app->translations, "cache")   &&
            $this->config->app->translations->cache === true
        ) {
            $cache  =   true;
        }

        try {
            // Set translator service/instance
            $this->translator   =   $this->container->get("Translator");
            $this->translator->setLanguagesPath(
                $this->rootPath . self::DS . $this->config->app->translations->path
            );

            // Get Session
            $bag    =   $this->session
                ->getSession()
                ->getBags()
                ->getBag("Comely")
                ->getBag("Framework");
            /** @var $currentLocale string|null */
            $currentLocale  =   $bag->get("language") ?? $this->config->app->translations->fallBack;

            // Source current and fallBack of language
            if($cache) {
                // Load compiled language from cache
                $locale =   $this->getCachedLanguage($currentLocale);
                $fallBack   =   $locale;
                if($currentLocale   !== $this->config->app->translations->fallBack) {
                    $fallBack   =   $this->getCachedLanguage($this->config->app->translations->fallBack);
                }
            } else {
                // Fresh compile language files from Yaml source
                $locale   =   $this->translator->language($currentLocale);
                $fallBack   =   $locale;
                if($currentLocale   !== $this->config->app->translations->fallBack) {
                    $fallBack   =   $this->translator->language($this->config->app->translations->fallBack);
                }
            }

            $this->translator->bindLanguage($locale);
            $this->translator->bindFallbackLanguage($fallBack);
        } catch(\ComelyException $e) {
            throw new BootstrapException(__METHOD__, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register Translator (i18n) Component
     * @param string $lang
     * @return Translator\Language
     */
    protected function getCachedLanguage(string $lang) : Translator\Language
    {
        // Get cache Disk instance
        $cache  =   $this->disks->pull("cache");

        // Cached language file
        $langFile   =   sprintf("bootstrap.lang_%s.php.cache", strtolower($lang));
        try {
            $lang   =   unserialize(
                $cache->read($langFile)
            );
        } catch(DiskException $e) {
        }

        // Got language?
        if(!isset($lang)    ||  !$lang instanceof Translator\Language) {
            $lang   =  $this->translator->language($lang);

            // Write to cache
            $cache->write(
                $langFile,
                serialize($lang),
                Disk::WRITE_FLOCK
            );
        }

        // Return Language instance
        return $lang;
    }
}