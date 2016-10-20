<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Http;

/**
 * Class Page
 * @package Http
 */
class Page
{
    /** @var array */
    private $props;

    /**
     * Page constructor.
     */
    public function __construct()
    {
        $this->props    =   [
            "title" =>  "",
            "root"  =>  "",
            "csrfToken" =>  "",
            "index" =>  ["a" => 0, "b" => 0, "c" => 0]
        ];
    }

    /**
     * @param string $title
     * @return Page
     */
    public function setTitle(string $title) : self
    {
        $this->props["title"]   =   $title;
        return $this;
    }

    /**
     * @param int $a
     * @param int $b
     * @param int $c
     * @return Page
     */
    public function setIndex(int $a = 0, int $b = 0, int $c = 0) : self
    {
        $this->props["index"]   =   ["a" => $a, "b" => $b, "c" => $c];
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return Page
     */
    public function setProp(string $name, $value) : self
    {
        $this->props[$name] =   $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getArray() : array
    {
        return $this->props;
    }
}