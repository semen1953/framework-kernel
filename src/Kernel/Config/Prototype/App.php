<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Config\Prototype {

    use Comely\Framework\Kernel\Config\Prototype\App\Cache;
    use Comely\Framework\Kernel\Config\Prototype\App\ErrorHandler;
    use Comely\Framework\Kernel\Config\Prototype\App\Knit;
    use Comely\Framework\Kernel\Config\Prototype\App\Mailer;
    use Comely\Framework\Kernel\Config\Prototype\App\Security;
    use Comely\Framework\Kernel\Config\Prototype\App\Sessions;
    use Comely\Framework\Kernel\Config\Prototype\App\Translations;

    class App
    {
        /** @var Cache */
        public $cache;
        /** @var Mailer */
        public $mailer;
        /** @var ErrorHandler */
        public $errorHandler;
        /** @var Knit */
        public $knit;
        /** @var Security */
        public $security;
        /** @var Sessions */
        public $sessions;
        /** @var Translations */
        public $translations;

        /** @var null|string */
        public $timeZone;
    }
}

namespace Comely\Framework\Kernel\Config\Prototype\App {

    use Comely\Framework\Kernel\Config\Prototype\App\ErrorHandler\Screen;
    use Comely\Framework\Kernel\Config\Prototype\App\Mailer\SMTP;
    use Comely\Framework\Kernel\Config\Prototype\App\Sessions\Cookie;

    class Cache
    {
        /** @var bool */
        public $status;
        /** @var string */
        public $engine;
        /** @var null|string */
        public $host;
        /** @var null|string */
        public $port;
        /** @var bool */
        public $terminate;
    }

    class Mailer
    {
        /** @var string */
        public $agent;
        /** @var string */
        public $senderName;
        /** @var string */
        public $senderEmail;
        /** @var SMTP */
        public $smtp;
    }

    class ErrorHandler
    {
        /** @var string */
        public $format;
        /** @var bool */
        public $hideErrors;
        /** @var Screen */
        public $screen;
    }

    class Knit
    {
        /** @var string */
        public $compilerPath;
        /** @var bool */
        public $caching;
    }

    class Security
    {
        /** @var string */
        public $cipherKey;
        /** @var string */
        public $defaultHashAlgo;
    }

    class Sessions
    {
        /** @var bool */
        public $useCache;
        /** @var null|string */
        public $storagePath;
        /** @var null|string */
        public $storageDb;
        /** @var null|string|int */
        public $expire;
        /** @var bool */
        public $encrypt;
        /** @var Cookie */
        public $cookie;
        /** @var string|null */
        public $hashSalt;
        /** @var int|null */
        public $hashCost;
    }

    class Translations
    {
        /** @var string */
        public $path;
        /** @var null|string */
        public $fallBack;
        /** @var bool */
        public $cache;
    }
}

namespace Comely\Framework\Kernel\Config\Prototype\App\Mailer {
    class SMTP
    {
        /** @var string */
        public $host;
        /** @var int */
        public $port;
        /** @var int */
        public $timeOut;
        /** @var bool */
        public $useTls;
        /** @var string */
        public $auth;
        /** @var null|string */
        public $username;
        /** @var null|string */
        public $password;
        /** @var null|string */
        public $serverName;
    }
}

namespace Comely\Framework\Kernel\Config\Prototype\App\ErrorHandler {
    class Screen
    {
        /** @var bool */
        public $debugBacktrace;
        /** @var bool */
        public $triggeredErrors;
        /** @var bool */
        public $completePaths;
    }
}

namespace Comely\Framework\Kernel\Config\Prototype\App\Sessions {
    class Cookie {
        /** @var null|string|int */
        public $expire;
        /** @var null|string */
        public $path;
        /** @var null|string */
        public $domain;
        /** @var bool */
        public $secure;
        /** @var bool */
        public $httpOnly;
    }
}