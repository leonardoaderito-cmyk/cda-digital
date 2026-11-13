<?php

namespace App\Http\Middleware;

use App\Models\Auditoria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApenasAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $utilizador = $request->user();

        abort_unless($utilizador && $utilizador->is_admin && $utilizador->ativo, 403);

        $resposta = $next($request);

        // Autor: Leonardo (leonardoaderito-cmyk) - regista toda a accao de escrita feita por administradores
        if (! $request->isMethodSafe()) {
            $alvos = collect($request->route()->parameters())
                ->map(fn ($p) => is_object($p) ? class_basename($p).'#'.$p->getKey() : $p)
                ->implode(',');
            Auditoria::registar($request->route()->getName(), $alvos ?: null);
        }

        return $resposta;
    }
}
