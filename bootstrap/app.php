<?php

use App\Http\Middleware\ApenasAdmin;
use App\Http\Middleware\CabecalhosSeguranca;
use App\Http\Middleware\PaginaActiva;
use App\Http\Middleware\PermissaoAdmin;
use App\Http\Middleware\TrocarSenha;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
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
        $middleware->appendToGroup('web', TrocarSenha::class);
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

// Leonardo (leonardoaderito-cmyk): no alojamento partilhado a pasta publica fica fora do projecto (p. ex. ~/public_html).
// O pacote "partilhado" traz bootstrap/pasta-publica.php com esse caminho; vale para o site e para o artisan.
if (is_file(__DIR__.'/pasta-publica.php')) {
    $app->usePublicPath(require __DIR__.'/pasta-publica.php');
}

return $app;
