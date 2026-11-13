<?php

namespace App\Http\Controllers;

use App\Services\Assistente\Assistente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Leonardo (leonardoaderito-cmyk): pedidos da janela do Assistente CDA no site publico
class AssistenteController extends Controller
{
    public function perguntar(Request $request, Assistente $assistente): JsonResponse
    {
        $dados = $request->validate([
            'pergunta' => ['required', 'string', 'min:2', 'max:500'],
            'historico' => ['nullable', 'array', 'max:12'],
            'historico.*.papel' => ['required', 'in:utilizador,assistente'],
            'historico.*.texto' => ['required', 'string', 'max:4000'],
        ], [
            'pergunta.required' => 'Escreva a sua pergunta.',
            'pergunta.max' => 'A pergunta pode ter no máximo 500 caracteres.',
        ]);

        return response()->json($assistente->responder($dados['pergunta'], $dados['historico'] ?? []));
    }
}
