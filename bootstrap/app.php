<?php

use App\Http\Middleware\ApenasAdmin;
use App\Http\Middleware\CabecalhosSeguranca;
use App\Http\Middleware\PaginaActiva;
use App\Http\Middleware\PermissaoAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => ApenasAdmin::class,
            'permissao' => PermissaoAdmin::class,
            'pagina' => PaginaActiva::class,
        ]);
        $middleware->appendToGroup('web', CabecalhosSeguranca::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            return response()->view('errors.413', [
                'limite' => ini_get('post_max_size'),
            ], 413);
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
