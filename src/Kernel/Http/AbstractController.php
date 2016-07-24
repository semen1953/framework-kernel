<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Http;

use Comely\Framework\Kernel\Exception\HttpException;
use Comely\IO\Http\Controllers\ControllerInterface;
use Comely\IO\Http\Request;
use Comely\IO\Http\Request\Response;

/**
 * Class AbstractController
 * @package Comely\Framework\Kernel\Http
 */
abstract class AbstractController implements ControllerInterface
{
    const REST_METHOD_PARAM =   "action";

    protected $controller;
    protected $input;
    protected $method;
    protected $request;
    protected $response;

    /**
     * @param Request $request
     * @param Response $response
     * @throws \Comely\IO\Http\Exception\RequestException
     */
    public function init(Request $request, Response $response)
    {
        // Save all request related information
        $this->request  =   $request;
        $this->controller   =   $request->getController();
        $this->input    =   $request->getInput();
        $this->method   =   $request->getMethod();
        $this->response =   $response;

        // Get input params
        $params  =   $this->input->getData();

        try {
            if($this->method    === "GET") {
                // Single method for all get requests
                $callMethod =   "getView";
            } else {
                // Check if we have necessary param to build method name
                if(!array_key_exists(self::REST_METHOD_PARAM, $params)) {
                    throw new HttpException(
                        get_called_class(),
                        sprintf('Http requests must have required parameter "%s"', self::REST_METHOD_PARAM)
                    );
                }

                // Method name
                $callMethod =   $this->method . "_" . $params[self::REST_METHOD_PARAM];
                $callMethod =   \Comely::camelCase($callMethod);
            }

            // Check if method exists
            if(!method_exists($this, $callMethod)) {
                throw new HttpException(
                    get_called_class(),
                    sprintf(
                        'Request method "%1$s" not found',
                        $callMethod
                    )
                );
            }

            // Call method
            call_user_func([$this,$callMethod]);
        } catch(\Throwable $t) {
            // Check number of props in Response object,
            // ...populate "errors" property if there are multiple properties already
            if($this->response->count() !== 1) {
                $this->response->set("error", $t->getMessage());
            }
        }
    }
}