<?php

declare(strict_types=1);

namespace Vesp\Controllers\Data;

use Carbon\Carbon;
use League\Glide\Responses\PsrResponseFactory;
use League\Glide\ServerFactory;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Stream;
use Vesp\Controllers\ModelGetController;
use Vesp\Models\File;

class Image extends ModelGetController
{
    protected string $model = File::class;

    public function get(): ResponseInterface
    {
        $key = $this->getPrimaryKey();
        $c = (new $this->model())->newQuery();

        /** @var File $file */
        if (!$key || !$file = is_array($key) ? $c->where($key)->first() : $c->find($key)) {
            return $this->response->withStatus(404);
        }

        // Ability of extension for special processing
        if ($response = $this->handleFile($file)) {
            return $response;
        }

        // Default processing
        if (!str_starts_with($file->type, 'image/')) {
            return $this->response->withStatus(422);
        }
        $server = ServerFactory::create(
            [
                'base_url' => $this->request->getUri()->getPath(),
                'source' => $file->getFilesystem()->getBaseFilesystem(),
                'cache' => rtrim(getenv('CACHE_DIR') ?: sys_get_temp_dir(), '/') . '/image_cache/',
                'driver' => getenv('IMAGE_DRIVER') ?: 'gd',
            ]
        );

        $response = new PsrResponseFactory(
            $this->response,
            static function ($stream) {
                return new Stream($stream);
            }
        );
        $server->setResponseFactory($response);
        $path = implode('/', [$file->path, $file->file]);

        return $server->getImageResponse($path, $this->getProperties());
    }

    protected function handleFile($file): ?ResponseInterface
    {
        /** @var File $file */
        // SVG cannot be processed, so we output it as is
        if ($file->type === 'image/svg+xml') {
            return $this->outputFile($file);
        }

        // GIFs without image manipulations should have no changes to save animation
        if ($file->type === 'image/gif') {
            $properties = $this->getProperties();
            unset($properties['id'], $properties['uuid'], $properties['t']);
            if (isset($properties['fm']) && $properties['fm'] === 'gif') {
                unset($properties['fm']);
            }
            if (empty($properties)) {
                return $this->outputFile($file);
            }
        }

        return null;
    }

    protected function outputFile($file): ResponseInterface
    {
        /** @var File $file */
        $stream = new Stream($file->getFilesystem()->getBaseFilesystem()->readStream($file->getFilePathAttribute()));

        return $this->response
            ->withBody($stream)
            ->withHeader('Content-Type', $file->type)
            ->withHeader('Content-Length', $file->size)
            ->withHeader('Cache-Control', 'max-age=31536000, public')
            ->withHeader('Expires', Carbon::now()->addYear()->toRfc822String());
    }
}
