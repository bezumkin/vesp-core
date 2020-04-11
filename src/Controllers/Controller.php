<?php

namespace Vesp\Controllers;

use Illuminate\Database\Events\QueryExecuted;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
    /** @var User $user */
    protected $user;
    // Scope required to run controller
    protected $scope;
    // Stat and debug data
    protected $total_time = 0;
    protected $query_time = 0;
    protected $queries = 0;
    protected $debug = [];

    private $properties = [];

    /**
     * Controller constructor.
     * @param Eloquent $eloquent
     */
    public function __construct(Eloquent $eloquent)
    {
        $eloquent->bootEloquent();
        $this->eloquent = $eloquent;

        if (getenv('PROCESSORS_STAT') || getenv('PROCESSORS_DEBUG')) {
            $eloquent->getDatabaseManager()->listen(
                function ($query) use (&$count) {
                    /** @var QueryExecuted $query */
                    if (getenv('PROCESSORS_STAT')) {
                        $this->query_time += $query->time;
                        $this->queries++;
                    }
                    if (getenv('PROCESSORS_DEBUG')) {
                        foreach ($query->bindings as $v) {
                            $query->sql = preg_replace('#\?#', is_numeric($v) ? $v : "'{$v}'", $query->sql, 1);
                        }
                        $this->debug[] = $query->sql;
                    }
                }
            );
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @expectedException
     */
    public function process(RequestInterface $request, ResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        if ($user = $request->getAttribute('user')) {
            if ($user instanceof User) {
                $this->user = $user;
            }
        }

        $method = strtolower($request->getMethod());
        $this->setProperties($method === 'get' ? $request->getQueryParams() : $request->getParsedBody());

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
        if ($method === 'options' || !$this->scope || (PHP_SAPI == 'cli' && !getenv('PHPUNIT'))) {
            return true;
        }

        if ($this->scope && !$this->user) {
            return $this->failure('Authentication required', 401);
        }

        $scopes = $this->user->role->scope;

        return in_array($this->scope, $scopes) || in_array($this->scope . '/' . $method, $scopes)
            ? true
            : $this->failure('You have no permission for this action', 403);
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
        if (is_array($data)) {
            if (getenv('PROCESSORS_DEBUG')) {
                $data['debug'] = $this->debug;
            }
            if (getenv('PROCESSORS_STAT')) {
                $data['stat'] = [
                    'memory' => memory_get_peak_usage(true),
                    'queries' => $this->queries,
                    'query_time' => round(($this->query_time / 1000), 7),
                    'total_time' => round((microtime(true) - $this->total_time), 7),
                ];
            }
        }

        if ($data !== null) {
            $this->response
                ->getBody()
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        if (getenv('CORS')) {
            $this->response
                ->withHeader('Access-Control-Allow-Origin', '*');
        }

        return $this->response
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
            $response
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
     * @param $key
     * @param null $default
     *
     * @return mixed
     */
    public function getProperty($key, $default = null)
    {
        return isset($this->properties[$key])
            ? $this->properties[$key]
            : $default;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * @param $key
     */
    public function unsetProperty($key)
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
