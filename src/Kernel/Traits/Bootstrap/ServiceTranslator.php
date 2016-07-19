<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits\Bootstrap;

use Comely\Framework\KernelException;
use Comely\IO\Filesystem\Disk;
use Comely\IO\Filesystem\Exception\DiskException;
use Comely\IO\i18n\Translator\Language;

/**
 * Class ServiceTranslator
 * @package Comely\Framework\Kernel\Traits\Bootstrap
 */
trait ServiceTranslator
{
    /**
     * Error codes 20074-20077
     * @throws KernelException
     */
    private function registerTranslator()
    {
        if(!property_exists($this->config->app, "translations")) {
            throw KernelException::bootstrapError(
                '"translations" node must be set under "app" node', 20074
            );
        }

        if(!property_exists($this->config->app->translations, "path")) {
            throw KernelException::bootstrapError(
                'Var "path" to translations files must be set under "app.translations" node', 20075
            );
        }

        if(!property_exists($this->config->app->translations, "fallBack")) {
            throw KernelException::bootstrapError(
                'Var "fall_back" language must be set under "app.translations" node', 20076
            );
        }

        if(!isset($this->session)) {
            throw KernelException::bootstrapError(
                '"Translator" requires "Session" component', 20077
            );
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
            throw KernelException::bootstrapError($e->getMessage());
        }
    }

    /**
     * @param string $lang
     * @return Language
     */
    private function getCachedLanguage(string $lang) : Language
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
        if(!isset($lang)    ||  !$lang instanceof Language) {
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