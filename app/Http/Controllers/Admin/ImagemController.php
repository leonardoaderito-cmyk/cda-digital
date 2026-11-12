<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Autor: Leonardo (leonardoaderito-cmyk) - biblioteca de imagens
class ImagemController extends Controller
{
    public function index()
    {
        return view('admin.imagens', ['imagens' => Imagem::latest()->paginate(40)]);
    }

    /** Carrega imagens para a biblioteca. Pedidos do editor (JSON) recebem as imagens criadas. */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ficheiros' => ['required', 'array', 'max:20'],
            'ficheiros.*' => Imagem::REGRAS,
        ]);

        $novas = collect($request->file('ficheiros'))->map(fn ($ficheiro) => Imagem::guardar($ficheiro));

        if ($request->wantsJson()) {
            return response()->json(['imagens' => $novas->map(fn (Imagem $i) => [
                'id' => $i->id,
                'url' => $i->url(),
                'alt' => $i->alt ?: $i->nome,
                'nome' => $i->nome,
            ])->values()]);
        }

        $total = $novas->count();

        return back()->with('estado', $total === 1 ? 'Imagem adicionada.' : $total.' imagens adicionadas.');
    }

    public function update(Request $request, Imagem $imagem): RedirectResponse
    {
        $imagem->update($request->validate([
            'nome' => ['required', 'string', 'max:180'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]));

        return back()->with('estado', 'Imagem actualizada.');
    }

    public function destroy(Imagem $imagem): RedirectResponse
    {
        $imagem->apagarFicheiro();
        $imagem->delete();

        return back()->with('estado', 'Imagem removida.');
    }
}
