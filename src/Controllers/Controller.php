<?php

declare(strict_types=1);

namespace Vesp\Controllers;

use Illuminate\Database\Capsule\Manager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;
use Throwable;
use Vesp\Models\User;

abstract class Controller
{
    protected Manager $eloquent;

    protected RequestInterface $request;

    protected ResponseInterface $response;

    protected RouteInterface $route;

    protected ?User $user = null;

    protected string|array $scope = '';

    private array $properties = [];

    public function __construct(Manager $eloquent)
    {
        $this->eloquent = $eloquent;
    }

    protected function initController(RequestInterface $request, ResponseInterface $response): void
    {
        /** @var ServerRequestInterface $request */
        $routeContext = RouteContext::fromRequest($request);
        $this->route = $routeContext->getRoute();
        $this->request = $request;
        $this->response = $response;

        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->user = $user;
        }

        $method = strtolower($request->getMethod());
        $properties = ($method === 'get') ? $request->getQueryParams() : $request->getParsedBody() ?? [];
        if (is_array($properties)) {
            if ($method === 'delete') {
                $properties = array_merge($properties, $request->getQueryParams());
            }
            $properties = array_merge($properties, $this->route->getArguments());
            $this->setProperties($properties);
        }
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->initController($request, $response);

        $method = strtolower($request->getMethod());
        if ($noScope = $this->checkScope($method)) {
            return $noScope;
        }

        if (!method_exists($this, $method)) {
            return $this->failure('Method Not Allowed', 405);
        }

        try {
            return $this->{$method}();
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function checkScope(string $method): ?ResponseInterface
    {
        if ($method === 'options' || !$this->scope || (PHP_SAPI === 'cli' && !getenv('PHPUNIT'))) {
            return null;
        }

        if (!$this->user) {
            return $this->failure('Authentication required', 401);
        }
        $scope = $this->scope . '/' . $method;

        return !$this->user->hasScope($scope)
            ? $this->failure('You have no "' . $scope . '" scope for this action', 403)
            : null;
    }

    public function failure($message = '', int $code = 422, string $reason = ''): ResponseInterface
    {
        return $this->response($message, $code, $reason);
    }

    protected function response($data, int $status = 200, string $reason = ''): ResponseInterface
    {
        $response = $this->response;
        if ($data !== null) {
            $body = new Stream(fopen('php://temp', 'wb'));
            $body->write(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            $response = $response->withBody($body);
        }
        if (getenv('CORS')) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $this->request->getHeaderLine('HTTP_ORIGIN'));
        }

        return $response
            ->withStatus($status, $reason)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

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

    public function success($data = [], int $code = 200, string $reason = ''): ResponseInterface
    {
        return $this->response($data, $code, $reason);
    }

    public function getProperty(string $key, mixed $default = null): mixed
    {
        return $this->properties[$key] ?? $default;
    }

    public function setProperty(string $key, mixed $value): void
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

    protected function handleException(Throwable $e): ResponseInterface
    {
        return $this->failure($e->getMessage(), 500);
    }
}
