<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Leonardo (leonardoaderito-cmyk): quem entra com uma senha provisoria so pode usar o portal depois de a mudar
class TrocarSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->trocar_senha && ! $request->routeIs('senha.trocar', 'senha.trocar.guardar', 'logout')) {
            return redirect()->route('senha.trocar');
        }

        return $next($request);
    }
}
