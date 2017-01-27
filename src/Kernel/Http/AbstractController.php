<?php
declare(strict_types=1);

namespace Comely\Framework\Kernel\Http;

use Comely\Framework\Kernel;
use Comely\Framework\Kernel\Exception\HttpException;
use Comely\Framework\KernelException;
use Comely\IO\Http\Controllers\ControllerInterface;
use Comely\IO\Http\Request;
use Comely\IO\Http\Request\Response;
use Comely\Knit;

/**
 * Class AbstractController
 * @package Comely\Framework\Kernel\Http
 */
abstract class AbstractController implements ControllerInterface
{
    const REST_METHOD_PARAM =   "action";

    /** @var Kernel */
    protected $app;
    /** @var  ControllerInterface */
    protected $controller;
    /** @var  Request\Input */
    protected $input;
    /** @var  string */
    protected $method;
    /** @var Page */
    protected $page;
    /** @var  Request */
    protected $request;
    /** @var  Response */
    protected $response;
    /** @var string */
    protected $uri;

    /**
     * AbstractController constructor.
     * @param Kernel $kernel
     */
    public function __construct(Kernel $kernel)
    {
        $this->app  =   $kernel;
        $this->page =   new Page();
    }

    /**
     * @return mixed
     */
    abstract protected function callBack();

    /**
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * @param Knit $knit
     * @param string $tpl
     * @param string $param
     * @throws \Comely\Framework\KernelException
     */
    public function knit(Knit $knit, string $tpl, string $param = "body")
    {
        // Get CSRF token
        $csrfToken  =   $this->app->security()->csrf()->getToken();
        if(!$csrfToken) {
            // Set new CSRF token as it is not already set
            $csrfToken  =   $this->app->security()->csrf()->setToken();
        }

        // Set "csrfToken" prop in Page object
        $this->page->setProp("csrfToken", $csrfToken);

        // Assign variables to Knit
        $knit->assign("errors", $this->app->errorHandler()->fetchAll());
        $knit->assign("page", $this->page->getArray());
        $knit->assign("config", [
            "site" => $this->app->config()->getNode("site")
        ]);

        // Prepare template and set in Response object
        $template   =   $knit->prepare($tpl);
        $this->response->set($param, $template->getOutput());
    }

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
        $this->uri  =   $request->getUri();
        $this->page->setProp("root", $request->getUriRoot());

        // Get input params
        $params  =   $this->input->getData();

        try {
            // Check if we have necessary param to build method name
            if (!empty($params[self::REST_METHOD_PARAM])) {
                $callMethod = $this->method . "_" . $params[self::REST_METHOD_PARAM];
                $callMethod = \Comely::camelCase($callMethod);
            } else {
                // Necessary param not found
                switch ($this->method) {
                    // Check if HTTP method is GET
                    case "GET":
                        // Default method without explicit param is getView
                        $callMethod =   "getView";
                        break;
                    default:
                        // Check if a default method exists for request HTTP methods (i.e. postDefault)
                        $callMethod  =   sprintf('%sDefault', strtolower($this->method));
                        if(!method_exists($this, $callMethod)) {
                            throw new HttpException(
                                get_called_class(),
                                sprintf('Http requests must have required parameter "%s"', self::REST_METHOD_PARAM)
                            );
                        }
                }
            }


            // Check if method exists
            if (!method_exists($this, $callMethod)) {
                throw new HttpException(
                    get_called_class(),
                    sprintf(
                        'Request method "%1$s" not found',
                        $callMethod
                    )
                );
            }

            // Call "callBack" method prior to calling request method
            call_user_func([$this, "callBack"]);

            // Call method
            call_user_func([$this, $callMethod]);
        } catch (KernelException $e) {
            $this->response->set("message", $e->getMessage());
        }

        // Check number of props in Response object,
        if($this->response->count() !== 1) {
            // populate "errors" property if there are multiple properties already
            $this->response->set("errors", array_map(function(array $error) {
                return $error["formatted"];
            }, $this->app->errorHandler()->fetchAll()));
        }
    }
}