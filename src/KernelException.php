<?php
declare(strict_types=1);

namespace Comely\Framework;

/**
 * Class KernelException
 * @package Comely
 */
class KernelException extends \ComelyException
{
    protected static $componentId    =   "Comely\\Framework\\Kernel";

    /**
     * @return KernelException
     */
    public static function bootstrapped() : self
    {
        return new self(self::$componentId, 'Framework kernel has already been bootstrapped', 2001);
    }

    /**
     * @param string $method
     * @return KernelException
     */
    public static function notBootstrapped(string $method) : self
    {
        return new self($method, 'Framework kernel was not bootstrapped',  2002);
    }

    /**
     * @param string $id
     * @param string $key
     * @return KernelException
     */
    public static function badDbCredentials(string $id, string $key = null) : self
    {
        if(!isset($key)) {
            return new self(
                self::$componentId,
                sprintf('Credentials of "%1$s" database must be in an associative array', $id),
                2003
            );
        }

        return new self(
            self::$componentId,
            sprintf('Missing "%2$s" in credentials of database "%1$s"', $id, $key),
            2004
        );
    }

    /**
     * @param string $id
     * @return KernelException
     */
    public static function dbNotFound(string $id) : self
    {
        return new self(
            "Comely\\Framework\\Kernel::getDb",
            sprintf('Database with id "%1$s" not found', $id),
            2005
        );
    }

    /**
     * @param string $method
     * @param string $path
     * @return KernelException
     */
    public static function badDirectoryPath(string $method, string $path) : self
    {
        return new self($method, sprintf('"%1$s" is not a valid directory path', $path), 2006);
    }

    /**
     * @param string $method
     * @param string $node
     * @return KernelException
     */
    public static function badConfigNode(string $method, string $node) : self
    {
        return new self(
            $method,
            sprintf('Configuration node "%1$s" not found, or isn\'t a node', $node),
            2008
        );
    }

    /**
     * @param string $method
     * @return KernelException
     */
    public static function instanceNotAvailable(string $method) : self
    {
        return new self($method, 'Instance not available', 2009);
    }
}