<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\IO\Cache\Cache;
use Comely\IO\Cache\CacheException;
use Comely\IO\DependencyInjection\Repository;

/**
 * Class Memory
 * @package Comely\Framework\Kernel
 */
class Memory
{
    /** @var self */
    private static $instance;

    /** @var null|Cache */
    private $cache;
    /** @var Repository */
    private $repo;

    /**
     * @return Memory
     */
    public static function getInstance() : self
    {
        if(!isset(self::$instance)) {
            self::$instance =   new self();
        }

        return self::$instance;
    }

    /**
     * Memory constructor.
     */
    private function __construct()
    {
        $this->repo =   new Repository();
    }

    /**
     * @param Cache $cache
     * @return Memory
     */
    public function setCache(Cache $cache) : self
    {
        //$this->cache    =   $cache;
        return $this;
    }

    /**
     * @param string $key
     * @param string $instanceOf
     * @param callable|null $notFound
     * @return mixed|null
     */
    public function find(string $key, string $instanceOf, callable $notFound = null)
    {
        // Check in runtime memory
        if($this->repo->has($key)) {
            $pull   =   $this->repo->pull($key);
            if(is_object($pull) &&  is_a($pull, $instanceOf)) {
                return $pull;
            }
        }

        // Check in cache
        if(isset($this->cache)) {
            $cached =   $this->cache->get($key);
            if(is_object($cached)   &&  is_a($cached, $instanceOf)) {
                return $cached;
            }
        }

        if(is_callable($notFound)) {
            $callBack   =   call_user_func($notFound);
            if(is_object($callBack)) {
                $this->set($key, clone $callBack);
                return $callBack;
            }
        }

        return null;
    }

    /**
     * @param string $key
     * @param $object
     * @return bool
     */
    public function set(string $key, $object) : bool
    {
        if(!is_object($object)) {
            return false;
        }

        $this->repo->push($object, $key);
        if($this->cache) {
            try {
                $this->cache->set($key, clone $object);
            } catch (CacheException $e) {
                trigger_error($e->getParsed(), E_USER_WARNING);
            }
        }

        return true;
    }
}