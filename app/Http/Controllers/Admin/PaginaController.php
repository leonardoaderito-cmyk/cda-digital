<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Imagem;
use App\Models\Pagina;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Autor: Leonardo (leonardoaderito-cmyk) - gestao das paginas do portal
class PaginaController extends Controller
{
    public function index(Request $request)
    {
        $filtro = $request->query('estado');

        return view('admin.paginas', [
            'paginas' => Pagina::query()
                ->when($filtro === 'activas', fn ($q) => $q->where('activa', true))
                ->when($filtro === 'inactivas', fn ($q) => $q->where('activa', false))
                ->orderBy('tipo')->orderBy('titulo')->get(),
            'totais' => ['todas' => Pagina::count(), 'activas' => Pagina::where('activa', true)->count(), 'inactivas' => Pagina::where('activa', false)->count()],
            'filtro' => $filtro,
        ]);
    }

    public function create()
    {
        return view('admin.pagina_form', ['pagina' => new Pagina(['activa' => true]), 'imagens' => Imagem::latest()->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        $base = Str::slug($dados['titulo']) ?: 'pagina';
        $slug = $base;
        // O endereco nao pode coincidir com outra pagina nem com uma rota do portal
        for ($i = 2; Pagina::where('slug', $slug)->exists() || Route::has($slug); $i++) {
            $slug = $base.'-'.$i;
        }

        $pagina = Pagina::create($dados + ['slug' => $slug, 'tipo' => 'conteudo']);
        Pagina::esquecerEstados();

        return redirect()->route('admin.paginas.editar', $pagina)->with('estado', 'Página criada.');
    }

    public function edit(Pagina $pagina)
    {
        return view('admin.pagina_form', ['pagina' => $pagina, 'imagens' => Imagem::latest()->get()]);
    }

    public function update(Request $request, Pagina $pagina): RedirectResponse
    {
        if ($pagina->eSistema()) {
            // Numa seccao do portal so o titulo e o estado sao editaveis
            $pagina->update($request->validate(['titulo' => ['required', 'string', 'max:200']]) + ['activa' => $request->boolean('activa')]);
        } else {
            $pagina->update($this->validar($request));
        }
        Pagina::esquecerEstados();

        return back()->with('estado', 'Página actualizada.');
    }

    public function alternar(Pagina $pagina): RedirectResponse
    {
        $pagina->update(['activa' => ! $pagina->activa]);
        Pagina::esquecerEstados();

        return back()->with('estado', $pagina->activa ? 'Página activada.' : 'Página desactivada.');
    }

    public function destroy(Pagina $pagina): RedirectResponse
    {
        // As seccoes do portal e as paginas com rota propria so podem ser desactivadas
        abort_if($pagina->eSistema() || Route::has($pagina->slug), 403);
        $pagina->delete();
        Pagina::esquecerEstados();

        return redirect()->route('admin.paginas')->with('estado', 'Página removida.');
    }

    private function validar(Request $request): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'resumo' => ['nullable', 'string', 'max:500'],
            'conteudo' => ['nullable', 'string', 'max:500000'],
        ]);

        return [
            'titulo' => $dados['titulo'],
            'resumo' => $dados['resumo'] ?? null,
            'conteudo' => Pagina::limparHtml($dados['conteudo'] ?? ''),
            'activa' => $request->boolean('activa'),
            'no_menu' => $request->boolean('no_menu'),
        ];
    }
}
