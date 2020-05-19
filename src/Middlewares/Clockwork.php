<?php

namespace Vesp\Middlewares;

use Clockwork\DataSource\PsrMessageDataSource;
use Clockwork\Helpers\ServerTiming;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Vesp\Services\Clockwork as Service;
use Vesp\Services\Eloquent;

class Clockwork
{
    /** @var Eloquent $eloquent */
    protected $eloquent;

    /** @var Service $clockwork */
    protected $clockwork;

    /** @var float $startTime */
    protected $startTime;

    /**
     * Autoload database connection into middleware
     * @param Eloquent $eloquent
     * @param Service $clockwork
     */
    public function __construct(Eloquent $eloquent, Service $clockwork)
    {
        $this->eloquent = $eloquent;
        $this->clockwork = $clockwork;
        $this->startTime = microtime(true);
    }

    /**
     * @param Request $request
     * @param RequestHandler $handler
     * @return ResponseInterface
     */
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $request = $request->withAttribute('clockwork', $this->clockwork);
        $response = $handler->handle($request);

        $this->clockwork->getTimeline()->finalize($this->startTime);
        $this->clockwork->addDataSource(new PsrMessageDataSource($request, $response));

        $this->clockwork->resolveRequest();
        $this->clockwork->storeRequest();

        $clockworkRequest = $this->clockwork->getRequest();
        $response = $response
            ->withHeader('X-Clockwork-Id', $clockworkRequest->id)
            ->withHeader('X-Clockwork-Version', Service::VERSION);

        return $response->withHeader('Server-Timing', ServerTiming::fromRequest($clockworkRequest)->value());
    }
}
