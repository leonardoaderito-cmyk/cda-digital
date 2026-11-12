<?php

namespace App\Http\Middleware;

use App\Models\Pagina;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Autor: Leonardo (leonardoaderito-cmyk) - uma seccao desactivada na administracao responde 404
class PaginaActiva
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        abort_unless(Pagina::estaActiva($slug), 404);

        return $next($request);
    }
}
