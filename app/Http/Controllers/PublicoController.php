<?php

namespace App\Http\Controllers;

use App\Models\Actividade;
use App\Models\ContactoMensagem;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\MembroPublico;
use App\Models\NewsletterSubscricao;
use App\Models\Noticia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicoController extends Controller
{
    public function inicio()
    {
        return view('publico.inicio', [
            'noticias' => Noticia::where('publicada', true)->orderByDesc('data')->limit(9)->get(),
            'actividades' => Actividade::orderByDesc('data')->limit(4)->get(),
            'eventos' => Evento::where('activo', true)
                ->where(fn ($query) => $query->whereNull('data_evento')->orWhereDate('data_evento', '>=', today()))
                ->orderByRaw('data_evento IS NULL')
                ->orderBy('data_evento')
                ->limit(4)
                ->get(),
            'tiposDocumento' => Documento::selectRaw('tipo, count(*) as total')->groupBy('tipo')->orderByDesc('total')->get(),
            'totais' => [
                'membros' => MembroPublico::where('activo', true)->count(),
                'documentos' => Documento::count(),
                'noticias' => Noticia::where('publicada', true)->count(),
            ],
        ]);
    }

    public function noticias()
    {
        return view('publico.noticias', [
            'noticias' => Noticia::where('publicada', true)->orderByDesc('data')->paginate(9),
        ]);
    }

    public function noticia(string $slug)
    {
        return view('publico.noticia', [
            'noticia' => Noticia::where('slug', $slug)->where('publicada', true)->firstOrFail(),
        ]);
    }

    public function eventos()
    {
        return view('publico.eventos', [
            'eventos' => Evento::where('activo', true)->orderByDesc('data_evento')->orderByDesc('id')->paginate(9),
        ]);
    }

    public function evento(string $slug)
    {
        return view('publico.evento', [
            'evento' => Evento::where('slug', $slug)->where('activo', true)->firstOrFail(),
        ]);
    }

    public function actividades()
    {
        return view('publico.actividades', [
            'actividades' => Actividade::orderByDesc('data')->paginate(12),
        ]);
    }

    public function documentos(Request $request)
    {
        $q = Documento::query()
            ->when($request->filled('q'), fn ($c) => $c->where('titulo', 'like', '%'.$request->q.'%'))
            ->when($request->filled('tipo'), fn ($c) => $c->where('tipo', $request->tipo))
            ->when($request->filled('ano'), fn ($c) => $c->where('ano', (int) $request->ano))
            ->orderByDesc('ano')->orderBy('titulo');

        return view('publico.documentos', [
            'documentos' => $q->paginate(20)->withQueryString(),
            'tipos' => Documento::whereNotNull('tipo')->distinct()->orderBy('tipo')->pluck('tipo'),
        ]);
    }

    public function documento(Documento $documento)
    {
        $caminho = base_path('acervo/'.basename($documento->ficheiro));
        abort_unless(is_file($caminho), 404);

        return response()->file($caminho);
    }

    public function directorio(Request $request)
    {
        $q = MembroPublico::query()
            ->when($request->filled('q'), function ($c) use ($request) {
                $t = '%'.$request->q.'%';
                $c->where(fn ($w) => $w->where('nome', 'like', $t)->orWhere('empresa', 'like', $t)->orWhere('n_carta', 'like', $t));
            })
            ->when($request->filled('provincia'), fn ($c) => $c->where('provincia', $request->provincia))
            ->orderBy('nome');

        return view('publico.directorio', [
            'membros' => $q->paginate(30)->withQueryString(),
            'provincias' => MembroPublico::whereNotNull('provincia')->distinct()->orderBy('provincia')->pluck('provincia'),
        ]);
    }

    public function contactos()
    {
        return view('publico.contactos');
    }

    public function enviarContacto(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'email', 'max:200'],
            'assunto' => ['nullable', 'string', 'max:200'],
            'mensagem' => ['required', 'string', 'min:5', 'max:4000'],
        ]);

        ContactoMensagem::create($dados + ['ip' => $request->ip()]);

        return back()->with('estado', 'Mensagem enviada. A CDA respondera em breve.');
    }

    public function subscreverNewsletter(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'nome' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:200'],
        ]);

        NewsletterSubscricao::updateOrCreate(
            ['email' => mb_strtolower($dados['email'])],
            [
                'nome' => $dados['nome'] ?? null,
                'activo' => true,
                'origem' => 'portal',
                'ip' => $request->ip(),
            ],
        );

        return back()->with('estado', 'Subscrição confirmada. Passará a receber as novidades da CDA.');
    }
}
