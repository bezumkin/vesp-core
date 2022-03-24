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
    protected $model = File::class;

    public function get(): ResponseInterface
    {
        $id = $this->getPrimaryKey();
        /** @var File $file */
        if (!$id || !$file = (new $this->model())->newQuery()->find($id)) {
            return $this->response->withStatus(404);
        }
        if (strpos($file->type, 'image/') !== 0) {
            return $this->response->withStatus(422);
        }

        // GIFs without image manipulations should have no changes to save animation
        if ($file->type === 'image/gif') {
            $properties = $this->getProperties();
            unset($properties['id']);
            if (empty($properties)) {
                $this->response->getBody()->write($file->getFile());

                return $this->response
                    ->withHeader('Content-Type', $file->type)
                    ->withHeader('Content-Length', $file->size)
                    ->withHeader('Cache-Control', 'max-age=31536000, public')
                    ->withHeader('Expires', Carbon::now()->addYear()->toRfc822String());
            }
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
}
