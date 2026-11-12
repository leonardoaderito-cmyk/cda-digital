<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissaoAdmin
{
    public function handle(Request $request, Closure $next, string $permissao): Response
    {
        abort_unless($request->user()?->pode($permissao), 403);

        return $next($request);
    }
}
