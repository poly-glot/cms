<?php

declare(strict_types=1);

namespace App\Test\TestCase;

use App\Application;
use App\Middleware\HostHeaderMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use Cake\Core\Configure;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\TestSuite\TestCase;

class ApplicationTest extends TestCase
{
    public function testBootstrap(): void
    {
        Configure::write('debug', false);
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $app->bootstrap();
        $plugins = $app->getPlugins();

        $this->assertTrue($plugins->has('Bake'), 'plugins has Bake?');
        $this->assertTrue($plugins->has('Migrations'), 'plugins has Migrations?');
    }

    public function testMiddleware(): void
    {
        $app = new Application(dirname(__DIR__, 2) . '/config');
        $middleware = new MiddlewareQueue();

        $middleware = $app->middleware($middleware);

        $this->assertInstanceOf(SecurityHeadersMiddleware::class, $middleware->current());
        $middleware->seek(1);
        $this->assertInstanceOf(ErrorHandlerMiddleware::class, $middleware->current());
        $middleware->seek(2);
        $this->assertInstanceOf(HostHeaderMiddleware::class, $middleware->current());
        $middleware->seek(3);
        $this->assertInstanceOf(AssetMiddleware::class, $middleware->current());
        $middleware->seek(4);
        $this->assertInstanceOf(RoutingMiddleware::class, $middleware->current());
    }
}
