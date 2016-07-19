<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits\Bootstrap;

use Comely\Framework\KernelException;
use Comely\IO\Filesystem\Disk;
use Comely\IO\Toolkit\Time;

/**
 * Class ServiceSession
 * @package Comely\Framework\Kernel\Traits\Bootstrap
 */
trait ServiceSession
{
    /**
     * Error codes 20071-20073
     * @throws KernelException
     */
    private function registerSession()
    {
        // Container has Session component, must be defined in config.
        if(!property_exists($this->config->app, "sessions")) {
            throw KernelException::bootstrapError(
                '"sessions" node must be set under "app" node', 20071
            );
        }

        // Means of storage must be defined
        try {
            if(property_exists($this->config->app->sessions, "storageDb")) {
                $storage    =   $this->getDb($this->config->app->sessions->storageDb);
            } elseif(property_exists($this->config->app->sessions, "storagePath")) {
                $storage    =   new Disk(
                    $this->rootPath . self::DS . $this->config->app->sessions->storagePath
                );
            } else {
                // No storage configuration was set
                throw KernelException::bootstrapError(
                    'Variable "storage_db" or "storage_path" must be set under "app.sessions" node',
                    20072
                );
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
                        throw KernelException::bootstrapError(
                            'Cipher instance not found in Kernel\'s services container',
                            20073
                        );
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
            throw KernelException::bootstrapError($e->getMessage());
        }
    }
}