<?php

declare(strict_types=1);

namespace Vesp\Controllers;

use Clockwork\Clockwork;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Routing\RouteContext;
use Throwable;
use Vesp\Models\User;
use Vesp\Services\Eloquent;

abstract class Controller
{
    /** @var Eloquent $eloquent */
    protected $eloquent;

    /** @var RequestInterface $request */
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

    public function __construct(Eloquent $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    /**
     * @param RequestInterface|ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, ResponseInterface $response): ResponseInterface
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

        if ($noScope = $this->checkScope($method)) {
            return $noScope;
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
     * @param $method
     * @return ResponseInterface|null
     */
    public function checkScope($method): ?ResponseInterface
    {
        if ($method === 'options' || !$this->scope || (PHP_SAPI === 'cli' && !getenv('PHPUNIT'))) {
            return null;
        }

        if ($this->scope && !$this->user) {
            return $this->failure('Authentication required', 401);
        }
        $scope = $this->scope . '/' . $method;

        return !$this->user->hasScope($scope)
            ? $this->failure('You have no "' . $scope . '" scope for this action', 403)
            : null;
    }

    /**
     * @param string $message
     * @param int $code
     * @param string $reason
     * @return ResponseInterface
     */
    public function failure($message = '', int $code = 422, string $reason = ''): ResponseInterface
    {
        return $this->response($message, $code, $reason);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @param string $reason
     * @return ResponseInterface
     */
    protected function response($data, int $status = 200, string $reason = ''): ResponseInterface
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
    public function options(): ResponseInterface
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
     * @return ResponseInterface
     */
    public function success($data = [], int $code = 200, string $reason = ''): ResponseInterface
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
    public function setProperty(string $key, $value): void
    {
        $this->properties[$key] = $value;
    }

    public function unsetProperty(string $key): void
    {
        unset($this->properties[$key]);
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }
}
