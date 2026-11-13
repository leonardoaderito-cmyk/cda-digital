<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

// Autor: Leonardo (leonardoaderito-cmyk) - gestão de eventos e das suas páginas
class EventoController extends Controller
{
    public function index()
    {
        return view('admin.eventos', ['eventos' => Evento::orderByDesc('data_evento')->orderByDesc('id')->get()]);
    }

    public function create()
    {
        return view('admin.evento_form', ['evento' => new Evento(['blocos' => []])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $evento = new Evento;
        $this->preencher($request, $evento);

        return redirect()->route('admin.eventos.editar', $evento)->with('estado', 'Evento criado.');
    }

    public function edit(Evento $evento)
    {
        return view('admin.evento_form', ['evento' => $evento]);
    }

    public function update(Request $request, Evento $evento): RedirectResponse
    {
        $this->preencher($request, $evento);

        return back()->with('estado', 'Evento guardado.');
    }

    public function alternar(Evento $evento): RedirectResponse
    {
        $evento->update(['activo' => ! $evento->activo]);

        return back()->with('estado', $evento->activo ? 'Evento publicado.' : 'Evento desactivado.');
    }

    public function destroy(Evento $evento): RedirectResponse
    {
        $evento->delete();

        return redirect()->route('admin.eventos')->with('estado', 'Evento removido.');
    }

    private function preencher(Request $request, Evento $evento): void
    {
        $tipos = array_keys(Evento::TIPOS_BLOCO);
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'data_evento' => ['nullable', 'date'],
            'local' => ['nullable', 'string', 'max:200'],
            'resumo' => ['nullable', 'string', 'max:500'],
            'imagem_capa' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'blocos' => ['nullable', 'array', 'max:60'],
            'blocos.*.tipo' => ['required', 'in:'.implode(',', $tipos)],
            'blocos.*.conteudo' => ['nullable', 'string', 'max:5000'],
            'blocos.*.extra' => ['nullable', 'string', 'max:500'],
            'blocos.*.ficheiro' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $blocos = [];
        foreach ($dados['blocos'] ?? [] as $i => $bloco) {
            $conteudo = trim($bloco['conteudo'] ?? '');
            $extra = trim($bloco['extra'] ?? '');
            if ($bloco['tipo'] === 'imagem') {
                $ficheiro = $request->file("blocos.$i.ficheiro");
                $conteudo = $ficheiro ? $this->guardarImagem($ficheiro) : (str_starts_with($conteudo, 'img/eventos/') ? $conteudo : '');
            }
            if ($bloco['tipo'] === 'botao' && ! preg_match('#^(https?://|/)#i', $extra)) {
                continue;
            }
            if ($conteudo === '') {
                continue;
            }
            $blocos[] = ['tipo' => $bloco['tipo'], 'conteudo' => $conteudo, 'extra' => $extra];
        }

        $imagem = $evento->imagem;
        if ($request->hasFile('imagem_capa')) {
            $imagem = $this->guardarImagem($request->file('imagem_capa'));
        }

        $evento->fill([
            'titulo' => $dados['titulo'],
            'data_evento' => $dados['data_evento'] ?? null,
            'local' => $dados['local'] ?? null,
            'resumo' => $dados['resumo'] ?? null,
            'imagem' => $imagem,
            'blocos' => $blocos,
            'activo' => $request->boolean('activo'),
        ]);

        if (! $evento->exists) {
            $base = Str::slug($dados['titulo']) ?: 'evento';
            $slug = $base;
            for ($i = 2; Evento::where('slug', $slug)->exists(); $i++) {
                $slug = $base.'-'.$i;
            }
            $evento->slug = $slug;
        }

        $evento->save();
    }

    private function guardarImagem($ficheiro): string
    {
        File::ensureDirectoryExists(public_path('img/eventos'));
        $nome = Str::random(24).'.'.$ficheiro->extension();
        $ficheiro->move(public_path('img/eventos'), $nome);

        return 'img/eventos/'.$nome;
    }
}
