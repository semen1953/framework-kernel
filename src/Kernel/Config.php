<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\IO\Yaml\Yaml;

class Config
{
    /**
     * Config constructor.
     * @param string $configFile
     */
    public function __construct(string $configFile)
    {
        $parsed =   Yaml::getParser()->parse($configFile);
        $this->setProperties($parsed, $this);
        
    }

    /**
     * @param array $data
     * @param $object
     */
    private function setProperties(array $data, $object)
    {
        foreach($data as $key => $value) {
            $key    =   \Comely::camelCase($key);
            if(is_array($value)) {
                $object->$key =   new PlainObject();
                $this->setProperties($value, $object->$key); return;
            } else {
                $object->$key =   $value;
            }
        }
    }
}