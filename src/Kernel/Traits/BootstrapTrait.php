<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Traits;

use Comely\Framework\Kernel\Traits\Bootstrap\ServiceCipher;
use Comely\Framework\Kernel\Traits\Bootstrap\ServiceSession;
use Comely\Framework\Kernel\Traits\Bootstrap\ServiceTranslator;

/**
 * Class BootstrapTrait
 * @package Comely\Framework\Kernel\Traits
 */
trait BootstrapTrait
{
    use ServiceCipher;
    use ServiceSession;
    use ServiceTranslator;
}