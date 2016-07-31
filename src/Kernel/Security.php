<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\IO\Security\CSRF;
use Comely\IO\Security\Forms;

/**
 * Class Security
 * @package Comely\Framework\Kernel
 */
class Security
{
    private $sessionBag;
    private $csrf;
    private $forms;

    /**
     * Security constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->sessionBag   =   $kernel->getSession()->getBags()
            ->getBag("Comely")
            ->getBag("Framework")
            ->getBag("Security");
    }

    /**
     * @return CSRF
     */
    public function csrf() : CSRF
    {
        if(!isset($this->csrf)) {
            $this->csrf =   new CSRF($this->sessionBag->getBag("CSRF"));
        }
        
        return $this->csrf;
    }

    /**
     * @return Forms
     */
    public function forms() : Forms
    {
        if(!isset($this->forms)) {
            $this->forms    =   new Forms($this->sessionBag->getBag("Forms"));
        }
        
        return $this->forms;
    }
}