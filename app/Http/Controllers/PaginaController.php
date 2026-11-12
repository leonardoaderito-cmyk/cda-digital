<?php

namespace App\Http\Controllers;

use App\Models\Pagina;
use Illuminate\Support\Facades\Route;

// Autor: Leonardo (leonardoaderito-cmyk) - paginas de conteudo geridas na administracao
class PaginaController extends Controller
{
    public function mostrar(?string $slug = null)
    {
        $slug ??= Route::currentRouteName();
        $pagina = Pagina::where('slug', $slug)->where('tipo', 'conteudo')->first();

        if (! $pagina) {
            // Sem registo na base de dados, usa-se a vista original da pagina institucional
            abort_unless(view()->exists('institucional.'.$slug), 404);

            return view('institucional.'.$slug);
        }

        abort_unless($pagina->activa, 404);

        return view('publico.pagina', ['pagina' => $pagina]);
    }
}
