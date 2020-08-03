<?php

declare(strict_types=1);

namespace Vesp\Middlewares;

use Clockwork\DataSource\EloquentDataSource;
use Clockwork\DataSource\PsrMessageDataSource;
use Clockwork\DataSource\XdebugDataSource;
use Clockwork\Helpers\ServerTiming;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

    public function __construct(Eloquent $eloquent, Service $clockwork)
    {
        $this->eloquent = $eloquent;
        $this->clockwork = $clockwork;
        $this->startTime = microtime(true);
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $source = new EloquentDataSource($this->eloquent->getDatabaseManager(), $this->eloquent->getEventDispatcher());
        $source->listenToEvents();
        $this->clockwork->addDataSource($source);

        $request = $request->withAttribute('clockwork', $this->clockwork);
        $response = $handler->handle($request);

        $this->clockwork->getTimeline()->finalize($this->startTime);
        $this->clockwork->addDataSource(new PsrMessageDataSource($request, $response));
        if (function_exists('xdebug_get_profiler_filename')) {
            $this->clockwork->addDataSource(new XdebugDataSource());
        }

        $this->clockwork->resolveRequest();
        $this->clockwork->storeRequest();

        $clockworkRequest = $this->clockwork->getRequest();
        $response = $response
            ->withHeader('X-Clockwork-Id', $clockworkRequest->id)
            ->withHeader('X-Clockwork-Version', Service::VERSION);

        return $response->withHeader('Server-Timing', ServerTiming::fromRequest($clockworkRequest)->value());
    }
}
