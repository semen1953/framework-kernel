<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Exception\BootstrapException;
use Comely\IO\Cache\Cache;
use Comely\IO\Cache\CacheException;
use Comely\IO\DependencyInjection\Container;
use Comely\IO\DependencyInjection\Repository;
use Comely\IO\Emails\Mailer;
use Comely\IO\Filesystem\Disk;
use Comely\IO\Filesystem\Exception\DiskException;
use Comely\IO\i18n\Translator;
use Comely\IO\Security\Cipher;
use Comely\IO\Session\Session;
use Comely\IO\Session\Storage;
use Comely\IO\Toolkit\Time;
use Comely\Knit;

/**
 * Class Bootstrapper
 * @package Comely\Framework\Kernel
 */
abstract class Bootstrapper implements Constants
{
    /** @var null|Cache */
    protected $cache;
    /** @var Container */
    protected $container;
    /** @var Config */
    protected $config;
    /** @var null|Cipher */
    protected $cipher;
    /** @var null|Mailer */
    protected $mailer;
    /** @var null|Session */
    protected $session;
    /** @var null|Translator */
    protected $translator;
    /** @var null|Knit */
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
     * Register Cache Component
     * @throws BootstrapException
     */
    protected function registerCache()
    {
        // Container has Cache component, must be defined in config
        if(!property_exists($this->config->app, "cache")) {
            throw BootstrapException::cacheNode();
        }

        $cacheConfig    =   $this->config->app->cache; // Cache configuration

        // Make sure STATUS is set and is bool
        if(!property_exists($cacheConfig, "status") ||  !is_bool($cacheConfig->status)) {
            throw BootstrapException::cacheStatus();
        }

        // Not use cache?
        if($this->config->app->cache->status    !== true) {
            return; // Return
        }

        // Which cache engine to use?
        if(!property_exists($cacheConfig, "engine") ||  !is_string($cacheConfig->engine)) {
            throw BootstrapException::cacheEngine("");
        }

        switch (strtolower($cacheConfig->engine)) {
            case "redis":
                $cacheEngine    =   Cache::ENGINE_REDIS;
                break;
            case "memcached":
                $cacheEngine    =   Cache::ENGINE_MEMCACHED;
                break;
            default:
                throw BootstrapException::cacheEngine($cacheConfig->engine);
        }

        $cacheHost  =   $cacheConfig->host ?? "127.0.0.1";
        $cachePort  =   intval($cacheConfig->port ?? 8000);

        $this->cache    =   $this->container->get("Cache");
        $this->cache->addServer($cacheHost, $cachePort, 1, $cacheEngine);

        try {
            $this->cache->connect();
        } catch (\ComelyException $e) {
            if(property_exists($cacheConfig, "terminate")) {
                if($cacheConfig->terminate  === true) {
                    throw new BootstrapException(__METHOD__, $e->getMessage(), $e->getCode());
                }
            }

            // Trigger E_USER_WARNING
            trigger_error(
                sprintf(
                    'Failed to connect with %1$s server on %2$s:%3$d: %4$s',
                    strtoupper($cacheConfig->engine),
                    $cacheHost,
                    $cachePort,
                    $e->getMessage()
                ),
                E_USER_WARNING
            );
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

        $sessionsConfig =   $this->config->app->sessions;

        // Means of storage must be defined
        try {
            // Use Cache?
            if(property_exists($sessionsConfig, "useCache") &&  $sessionsConfig->useCache   === true) {
                if(!$this->cache instanceof Cache) {
                    throw BootstrapException::sessionCache();
                }

                try {
                    $this->cache->poke(false); // Poke cache engine; Don't reconnect
                    $cache  =   $this->cache; // Ref. cache instance
                } catch (CacheException $e) {
                    trigger_error(sprintf('%s: %s', __METHOD__, $e->getMessage()), E_USER_WARNING);
                }
            }

            /** @var $this Kernel */
            if(isset($cache)    &&  $cache instanceof Cache) {
                $storage    =   Storage::Cache($cache);
            } elseif(property_exists($sessionsConfig, "storageDb")) {
                $storage    =   Storage::Database($this->getDb($sessionsConfig->storageDb));
            } elseif(property_exists($sessionsConfig, "storagePath")) {
                $storage    =   Storage::Disk(
                    new Disk($this->rootPath . self::DS . $sessionsConfig->storagePath)
                );
            } else {
                // No storage configuration was set
                throw BootstrapException::sessionStorage();
            }

            // Retrieve session instance
            $this->session  =   $this->container->get("Session", $storage);

            // Session configuration
            // Expiry
            if(property_exists($sessionsConfig, "expire")) {
                $this->session->setSessionLife(Time::unitsToSeconds($sessionsConfig->expire));
            }

            // Encryption
            if(property_exists($sessionsConfig, "encrypt")) {
                if($sessionsConfig->encrypt    === true) {
                    if(!isset($this->cipher)) {
                        throw BootstrapException::cipherService();
                    }

                    $this->session->useCipher($this->cipher);
                }
            }

            // Cookie
            $cookie =   [false, "30d", "", "", false, true];
            if(property_exists($sessionsConfig, "cookie")) {
                $cookie[0]  =   true;
                $cookieArgCount =   1;
                foreach(["expire","path","domain","secure","httpOnly"] as $cookieArg) {
                    if(property_exists($sessionsConfig->cookie, $cookieArg)) {
                        $cookie[$cookieArgCount] =   $sessionsConfig->cookie->$cookieArg;
                    }

                    $cookieArgCount++;
                }
            }

            $cookie[1]  =   Time::unitsToSeconds($cookie[1]); // Life
            $cookie[2]  =   $cookie[2] ?? ""; // Path
            $cookie[3]  =   $cookie[3] ?? ""; // Domain
            call_user_func_array([$this->session,"setCookie"], $cookie);

            // PBKDF2 Hashing
            // Salt
            if(property_exists($sessionsConfig, "hashSalt")) {
                $this->session->setHashSalt(strval($sessionsConfig->hashSalt));
            }

            // Cost
            if(property_exists($sessionsConfig, "hashCost")) {
                $this->session->setHashCost(intval($sessionsConfig->hashCost));
            }

            // Bootstrap Session
            $this->session->start();

            // If storage is filesystem, save instance in disks repo.
            if($storage instanceof Storage\Disk) {
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
        if(property_exists($this->config->app->translations, "cache")) {
            if($this->config->app->translations->cache === true) {
                $cache  =   true;
            }
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
            $bag->set("language", $currentLocale);
        } catch(\ComelyException $e) {
            throw new BootstrapException(__METHOD__, $e->getMessage(), $e->getCode());
        }
    }

    /**
     * Register Translator (i18n) Component
     * @param string $lang
     * @return Translator\Language
     */
    public function getCachedLanguage(string $lang) : Translator\Language
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

    /**
     * Register Mailer Component
     * @throws BootstrapException
     */
    protected function registerMailer()
    {
        // Container has Mailer component, must be defined in config
        if(!property_exists($this->config->app, "mailer")) {
            throw BootstrapException::mailerNode();
        }

        $mailerConfig   =   $this->config->app->mailer;
        if(!property_exists($mailerConfig, "agent") ||  !is_string($mailerConfig->agent)) {
            throw BootstrapException::mailerAgent("");
        }

        switch (strtolower($mailerConfig->agent)) {
            case "sendmail":
                $useSMTP    =   false;
                break;
            case "smtp";
                $useSMTP    =   true;
                break;
            default:
                throw BootstrapException::mailerAgent($mailerConfig->agent);
        }

        $this->mailer   =   $this->container->get("Mailer"); // Grab Mailer Instance

        // Configure default sender name and email
        $senderName =   $mailerConfig->senderName ?? null;
        if(is_string($senderName)) {
            $this->mailer->senderName($senderName);
        }

        $senderEmail    =   $mailerConfig->senderEmail ?? null;
        if(is_string($senderEmail)) {
            $this->mailer->senderEmail($senderEmail);
        }

        // Configure SMTP
        if($useSMTP) {
            $smtpHost   =   $mailerConfig->smtp->host ?? "127.0.0.1";
            $smtpPort   =   intval($mailerConfig->smtp->port ?? 25);
            $smtpTimeout    =   intval($mailerConfig->smtp->timeOut ?? 1);
            $smtpUsername   =   $mailerConfig->smtp->username ?? "";
            $smtpPassword   =   $mailerConfig->smtp->password ?? "";
            $smtpTLS    =   $mailerConfig->smtp->useTls ?? true;
            $smtpTLS    =   $smtpTLS    === false ? false : true;
            $smtpServerName =   $mailerConfig->smtp->serverName ?? null;

            $smtp   =   (new Mailer\SMTP($smtpHost, $smtpPort, $smtpTimeout))
                ->useTLS($smtpTLS);
            if(!empty($smtpUsername)    &&  !empty($smtpPassword)) {
                $smtp->authCredentials((string) $smtpUsername, (string) $smtpPassword);
            }

            if(!empty($smtpServerName)  &&  is_string($smtpServerName)) {
                $smtp->serverName($smtpServerName);
            }

            // Bind SMTP agent to mailer
            $this->mailer->bindAgent($smtp);
        }
    }
}