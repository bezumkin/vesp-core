<?php

declare(strict_types=1);

namespace Vesp\Controllers\Data;

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

        $server = ServerFactory::create(
            [
                'base_url' => $this->request->getUri()->getPath(),
                'source' => $file->getFilesystem()->getBaseFilesystem(),
                'cache' => rtrim(getenv('TMP_DIR') ?: sys_get_temp_dir(), '/') . '/image_cache/',
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
