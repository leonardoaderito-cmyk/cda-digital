<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// Autor: Leonardo (leonardoaderito-cmyk) - biblioteca de imagens
class ImagemController extends Controller
{
    public function index()
    {
        return view('admin.imagens', ['imagens' => Imagem::latest()->paginate(40)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'ficheiros' => ['required', 'array', 'max:20'],
            'ficheiros.*' => Imagem::REGRAS,
        ]);

        foreach ($request->file('ficheiros') as $ficheiro) {
            Imagem::guardar($ficheiro);
        }

        $total = count($request->file('ficheiros'));

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
