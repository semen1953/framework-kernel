<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Database;

use Comely\Framework\Kernel;
use Comely\IO\Database\Fluent;
use Comely\IO\Database\Schema;

/**
 * Class AbstractFluentModel
 * @package Comely\Framework\Kernel\Database
 */
abstract class AbstractFluentModel extends Fluent implements \Serializable
{
    /** @var Kernel */
    protected $app;

    /**
     * This is the callBack method for Fluent models
     * This method is also called when models extending this class are un-serialised
     * @param Kernel $app
     */
    final public function callBack(Kernel $app)
    {
        $this->setKernelInstance($app);
        $this->fluentCallBack();
    }

    /**
     * @return mixed
     */
    abstract public function fluentCallBack();

    /**
     * @param Kernel $app
     */
    final public function setKernelInstance(Kernel $app)
    {
        $this->app  =   $app;
    }

    /**
     * @return string
     */
    final public function serialize()
    {
        $clone  =   clone $this;
        $clone->schemaTable =   get_class($clone->schemaTable);
        unset($clone->app);

        $reflect    =   new \ReflectionClass($clone);
        $props  =   [];
        /** @var $prop \ReflectionProperty */
        foreach($reflect->getProperties() as $prop) {
            $key    =   $prop->getName();
            $props[$key]    =   $clone->$key ?? null;
        }

        return serialize($props);
    }

    /**
     * @param string $serialized
     */
    final public function unserialize($serialized)
    {
        $props  =   unserialize($serialized);
        $reflect    =   new \ReflectionClass($this);
        /** @var $prop \ReflectionProperty */
        foreach($reflect->getProperties() as $prop) {
            $key    =   $prop->getName();
            $this->$key =   $props[$key] ?? null;
        }

        $this->schemaTable  =   Schema::getTable($this->schemaTable);
        call_user_func_array([$this, "callBack"], Schema::getCallbackArgs());
    }
}