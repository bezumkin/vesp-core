<?php

namespace Vesp\Tests;

use DI\Bridge\Slim\Bridge;
use Phinx\Config\Config;
use Phinx\Migration\Manager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var App $app */
    protected $app;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        file_put_contents(getenv('DB_DATABASE'), null); // Make sure we have a SQLite DB
        $config = new Config(
            [
                'paths' => ['migrations' => dirname(__DIR__) . '/db/migrations'],
                'environments' => [
                    'test' => [
                        'adapter' => getenv('DB_DRIVER'),
                        'name' => str_replace('.sqlite3', '', getenv('DB_DATABASE')),
                    ],
                ],
            ]
        );
        $phinx = new Manager($config, new ArgvInput(), new NullOutput());
        $phinx->migrate('test');
    }

    public static function tearDownAfterClass(): void
    {
        unlink(getenv('DB_DATABASE'));
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return ServerRequestInterface
     */
    public function createRequest($method, $uri, $params = []): RequestInterface
    {
        $method = strtoupper($method);
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);
        if ($method === 'GET') {
            $request = $request->withQueryParams($params);
        } else {
            $request = $request->withParsedBody($params);
        }

        return $request;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $app = Bridge::create();
        $this->app = $app;
    }
}
