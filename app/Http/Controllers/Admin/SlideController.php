<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Noticia;
use App\Models\Slide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Autor: Leonardo (leonardoaderito-cmyk) - configuracao do carrossel da pagina inicial
class SlideController extends Controller
{
    public function index()
    {
        return view('admin.slides', [
            'slides' => Slide::orderBy('ordem')->orderBy('id')->get(),
            'noticias' => Noticia::where('publicada', true)->orderByDesc('data')->limit(30)->get(),
        ]);
    }

    public function create()
    {
        return view('admin.slide_form', ['slide' => new Slide(['activo' => true]), 'imagens' => Imagem::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $slide = Slide::create($this->validar($request) + ['ordem' => (int) Slide::max('ordem') + 1]);

        return redirect()->route('admin.slides.editar', $slide)->with('estado', 'Slide criado.');
    }

    /** Cria um slide a partir de uma noticia publicada: titulo, resumo, imagem e ligacao. */
    public function daNoticia(Request $request): RedirectResponse
    {
        $noticia = Noticia::where('publicada', true)->findOrFail($request->validate(['noticia' => ['required', 'integer']])['noticia']);

        $slide = Slide::create([
            'titulo' => Str::limit($noticia->titulo, 250, ''),
            'etiqueta' => $noticia->categoria ? Str::limit($noticia->categoria, 60, '') : 'Notícia',
            'texto' => Str::limit($noticia->resumo, 200),
            'imagem' => $noticia->imagem && Str::startsWith($noticia->imagem, '/') ? $noticia->imagem : null,
            'botao_texto' => 'Ler mais',
            'botao_url' => '/noticias/'.$noticia->slug,
            'ordem' => (int) Slide::max('ordem') + 1,
            'activo' => true,
        ]);

        return redirect()->route('admin.slides.editar', $slide)->with('estado', 'Slide criado a partir da notícia.');
    }

    public function edit(Slide $slide)
    {
        return view('admin.slide_form', ['slide' => $slide, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Slide $slide): RedirectResponse
    {
        $slide->update($this->validar($request, $slide));

        return back()->with('estado', 'Slide actualizado.');
    }

    public function alternar(Slide $slide): RedirectResponse
    {
        $slide->update(['activo' => ! $slide->activo]);

        return back()->with('estado', $slide->activo ? 'Slide activado.' : 'Slide desactivado.');
    }

    /** Troca o slide de posicao com o vizinho de cima ou de baixo. */
    public function mover(Request $request, Slide $slide): RedirectResponse
    {
        $ids = Slide::orderBy('ordem')->orderBy('id')->pluck('id')->all();
        $posicao = array_search($slide->id, $ids, true);
        $destino = $posicao + ($request->input('sentido') === 'subir' ? -1 : 1);

        if (isset($ids[$destino])) {
            [$ids[$posicao], $ids[$destino]] = [$ids[$destino], $ids[$posicao]];
            foreach ($ids as $ordem => $id) {
                Slide::whereKey($id)->update(['ordem' => $ordem + 1]);
            }
        }

        return back();
    }

    public function destroy(Slide $slide): RedirectResponse
    {
        $slide->delete();

        return redirect()->route('admin.slides')->with('estado', 'Slide removido.');
    }

    private function validar(Request $request, ?Slide $slide = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:250'],
            'etiqueta' => ['nullable', 'string', 'max:60'],
            'texto' => ['nullable', 'string', 'max:400'],
            'botao_texto' => ['nullable', 'string', 'max:60'],
            // So enderecos do proprio portal (/...) ou http(s)
            'botao_url' => ['nullable', 'string', 'max:500', 'regex:#^(/(?!/)|https?://)#i'],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ], ['botao_url.regex' => 'A ligação do botão tem de começar por / (página do portal) ou por http:// ou https://.']);

        $imagem = $slide?->imagem;
        if ($request->hasFile('imagem_ficheiro')) {
            $imagem = Imagem::guardar($request->file('imagem_ficheiro'), $dados['titulo'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $imagem = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        } elseif ($request->boolean('remover_imagem')) {
            $imagem = null;
        }

        return [
            'titulo' => $dados['titulo'],
            'etiqueta' => $dados['etiqueta'] ?? null,
            'texto' => $dados['texto'] ?? null,
            'botao_texto' => $dados['botao_texto'] ?? null,
            'botao_url' => $dados['botao_url'] ?? null,
            'imagem' => $imagem,
            'activo' => $request->boolean('activo'),
        ];
    }
}
