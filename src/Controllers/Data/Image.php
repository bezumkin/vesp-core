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
        $id = $this->getPrimaryKey();
        /** @var File $file */
        if (!$id || !$file = (new $this->model())->newQuery()->find($id)) {
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

    protected function handleFile(File $file): ?ResponseInterface
    {
        // SVG cannot be processed, so we output it as is
        if ($file->type === 'image/svg+xml') {
            return $this->outputFile($file);
        }

        // GIFs without image manipulations should have no changes to save animation
        if ($file->type === 'image/gif') {
            $properties = $this->getProperties();
            unset($properties['id']);
            if (empty($properties)) {
                return $this->outputFile($file);
            }
        }

        return null;
    }

    protected function outputFile(File $file): ResponseInterface
    {
        $this->response->getBody()->write($file->getFile());

        return $this->response
            ->withHeader('Content-Type', $file->type)
            ->withHeader('Content-Length', $file->size)
            ->withHeader('Cache-Control', 'max-age=31536000, public')
            ->withHeader('Expires', Carbon::now()->addYear()->toRfc822String());
    }
}
