<?php

namespace Vesp\Controllers;

use Clockwork\Clockwork;
use Psr\Http\Message\ResponseInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Psr7\Request;
use Slim\Routing\RouteContext;
use Throwable;
use Vesp\Models\User;
use Vesp\Services\Eloquent;

abstract class Controller
{
    /** @var Eloquent $eloquent */
    protected $eloquent;

    /** @var Request $request */
    protected $request;

    /** @var ResponseInterface $response */
    protected $response;

    /** @var RouteInterface $route */
    protected $route;

    /** @var User $user */
    protected $user;

    /** @var Clockwork $clockwork */
    protected $clockwork;

    // Scope required to run controller
    protected $scope;

    private $properties = [];

    /**
     * Controller constructor.
     * @param Eloquent $eloquent
     */
    public function __construct(Eloquent $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    /**
     * @param Request $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @expectedException
     */
    public function process(Request $request, ResponseInterface $response)
    {
        $routeContext = RouteContext::fromRequest($request);
        $this->route = $routeContext->getRoute();
        $this->request = $request;
        $this->response = $response;

        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->user = $user;
        }
        $clockwork = $request->getAttribute('clockwork');
        if ($clockwork instanceof Clockwork) {
            $this->clockwork = $clockwork;
        }

        $method = strtolower($request->getMethod());
        $properties = ($method === 'get') ? $request->getQueryParams() : $request->getParsedBody();
        if (is_array($properties)) {
            $this->setProperties($properties);
        }

        $check = $this->checkScope($method);
        if ($check instanceof ResponseInterface) {
            return $check;
        }

        if (!method_exists($this, $method)) {
            return $this->failure('Could not find requested method', 404);
        }
        // @codeCoverageIgnoreStart
        // due to weird bug in test coverage for try catch
        try {
            return $this->{$method}();
        } catch (Throwable $e) {
            return $this->failure($e->getMessage(), 500);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param string $method
     * @return bool|ResponseInterface
     */
    public function checkScope($method)
    {
        if ($method === 'options' || !$this->scope || (PHP_SAPI === 'cli' && !getenv('PHPUNIT'))) {
            return true;
        }

        if ($this->scope && !$this->user) {
            return $this->failure('Authentication required', 401);
        }

        $scope = $this->scope . '/' . $method;

        return $this->user->hasScope($scope)
            ? true
            : $this->failure('You have no "' . $scope . '" scope for this action', 403);
    }

    /**
     * @param string $message
     * @param int $code
     * @param string $reason
     *
     * @return ResponseInterface
     */
    public function failure($message = '', $code = 422, $reason = '')
    {
        return $this->response($message, $code, $reason);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @param string $reason
     *
     * @return ResponseInterface
     */
    protected function response($data, $status = 200, $reason = '')
    {
        $response = $this->response;
        if ($data !== null) {
            $response
                ->getBody()
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        if (getenv('CORS')) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $this->request->getHeaderLine('HTTP_ORIGIN'));
        }

        return $response
            ->withStatus($status, $reason)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return ResponseInterface
     */
    public function options()
    {
        $response = $this->success();
        if (getenv('CORS')) {
            $response = $response
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'POST, GET, HEAD, OPTIONS, DELETE, PUT, PATCH, UPDATE');
        }

        return $response;
    }

    /**
     * @param array $data
     * @param int $code
     * @param string $reason
     *
     * @return ResponseInterface
     */
    public function success($data = [], $code = 200, $reason = '')
    {
        return $this->response($data, $code, $reason);
    }

    /**
     * @param string $key
     * @param null|mixed $default
     *
     * @return mixed
     */
    public function getProperty(string $key, $default = null)
    {
        return $this->properties[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setProperty(string $key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function unsetProperty(string $key)
    {
        unset($this->properties[$key]);
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }
}
