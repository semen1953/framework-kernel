<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Exception\ErrorHandlerException;
use Comely\IO\Toolkit\Parser;

/**
 * Class ErrorHandler
 * @package Comely\Framework\Kernel
 */
class ErrorHandler
{
    private $format;
    private $logged;
    private $method;
    private $ignoreNotice;

    /**
     * ErrorHandler constructor.
     */
    public function __construct()
    {
        $this->format   =   '[%type|strtoupper%] %message% in %file|basename% on %line%';
        $this->method   =   Kernel::ERRORS_DEFAULT;
        $this->ignoreNotice =   false;
        $this->flush();
        
        set_error_handler([$this, "handleError"]);
        set_exception_handler(function(\Throwable $ex) {
           // TODO: Shutdown
        });
    }

    /**
     * Handle an error message
     *
     * @param int $type
     * @param string $message
     * @param string $file
     * @param int $line
     * @param array|null $context
     * @return bool
     */
    public function handleError(int $type, string $message, string $file, int $line, array $context = null)
    {
        if(error_reporting()    === 0)  return false;

        if(in_array($type, [2,8,512,1024,2048,8192,16384])) {
            $ignore =   $type   === 8 ? $this->ignoreNotice : false;
            if(!$ignore) {
                $error  =   [
                    "type"  =>  $this->errorType($type),
                    "message"   =>  $message,
                    "file"  =>  $file,
                    "line"  =>  $line
                ];

                $parser =   Parser::getInstance();
                $error["formatted"] =   $parser->parse($this->format, $error);

                if($this->method    !=  Kernel::ERRORS_PUBLISH) {
                    $this->logged[] =   $error;
                }

                if($this->method    !=  Kernel::ERRORS_COLLECT) {
                    print $error["formatted"] . "<br>" . Kernel::EOL;
                }
            }
        } else {
            // TODO: Shutdown
        }
    }

    /**
     * Sets an error handling flag
     *
     * @param $flag
     * @return ErrorHandler
     * @throws ErrorHandlerException
     */
    public function setFlag($flag) : self
    {
        switch($flag) {
            case Kernel::ERRORS_DEFAULT:
                $this->method  =   Kernel::ERRORS_DEFAULT;
                break;
            case Kernel::ERRORS_COLLECT:
                $this->method   =   Kernel::ERRORS_COLLECT;
                break;
            case Kernel::ERRORS_PUBLISH:
                $this->method   =   Kernel::ERRORS_PUBLISH;
                break;
            case Kernel::ERRORS_IGNORE_NOTICE:
                $this->ignoreNotice =   true;
                break;
            case Kernel::ERRORS_REPORT_ALL:
                $this->ignoreNotice =   false;
                break;
            default:
                throw ErrorHandlerException::badFlag(__METHOD__);
        }

        return $this;
    }

    /**
     * Set formatting for error messages
     *
     * @param string $format
     * @return ErrorHandler
     */
    public function setFormat(string $format) : self
    {
        $this->format   =   $format;
        return $this;
    }

    /**
     * Fetch all logged error messages
     *
     * @return array
     */
    public function fetchAll() : array
    {
        return $this->logged;
    }

    /**
     * Flush all logged error messages
     */
    public function flush()
    {
        $this->logged   =   [];
    }

    /**
     * @param int $error
     * @return string
     */
    private function errorType(int $error) : string
    {
        switch($error) {
            case 1: return  "Fatal Error";
            case 2: return  "Warning";
            case 4: return  "Parse Error";
            case 8: return  "Notice";
            case 16:    return  "Core Error";
            case 32:    return  "Core Warning";
            case 64:    return  "Compile Error";
            case 128:   return  "Compile Warning";
            case 256:   return  "Error";
            case 512:   return  "Warning";
            case 1024:  return  "Notice";
            case 2048:  return  "Strict";
            case 4096:  return  "Recoverable";
            case 8192:  return  "Deprecated";
            case 16384: return  "Deprecated";
            default:
                return "Unknown";
        }
    }
}