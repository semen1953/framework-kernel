<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\Framework\KernelException;
use Comely\IO\Database\Database;

trait DatabasesTrait
{
    private $databases;
    
    /**
     * Save database credentials
     *
     * Credentials are initially stored as associative arrays which are replaced with instances Database upon
     * first call to getDb() method
     *
     * @param array $dbs
     * @throws KernelException
     */
    private function setDatabases(array $dbs)
    {
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
        if(!isset($id)) {
            // No reference ID provided, fetch first database
            $db =   reset($this->databases);
            if(!$db) {
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
            // Create instance on first call
            $persistent =   (array_key_exists("persistent", $db)    &&  $db["persistent"]   === true) ? true : false;
            $this->databases[$id]   =   new Database(
                $db["driver"],
                $db["name"],
                $db["host"],
                $db["user"],
                $db["pass"],
                $persistent
            );
        }

        // Return instance of Comely\IO\Database\Database
        return $db;
    }
}