<?php

declare(strict_types=1);

namespace Vesp\Controllers\Data;

use Clockwork\DataSource\XdebugDataSource;
use Clockwork\Storage\FileStorage;
use Psr\Http\Message\ResponseInterface;
use Vesp\Controllers\Controller;
use Vesp\Services\Clockwork as Service;
use Vesp\Services\Eloquent;

class Clockwork extends Controller
{
    /** @var Service $clockwork */
    protected $clockwork;

    public function __construct(Eloquent $eloquent, Service $clockwork)
    {
        parent::__construct($eloquent);
        $this->clockwork = $clockwork;
    }

    public function get(): ResponseInterface
    {
        /** @var FileStorage $storage */
        $storage = $this->clockwork->getStorage();

        $id = $this->route->getArgument('id');
        $direction = $this->route->getArgument('direction');
        $count = $this->route->getArgument('count');

        if ($direction === 'previous') {
            $data = $storage->previous($id, $count);
        } elseif ($direction === 'next') {
            $data = $storage->next($id, $count);
        } elseif ($id === 'latest') {
            $data = $storage->latest();
        } elseif ($id) {
            $data = $storage->find($id);
            if ($data && strpos($this->request->getUri()->getPath(), 'extended') !== false) {
                $data = (new XdebugDataSource())->extend($data);
            }
        }

        return !empty($data)
            ? $this->success($data)
            : $this->failure('Not Found', 404);
    }
}
