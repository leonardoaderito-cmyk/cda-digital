<?php

namespace App\Http\Controllers;

use App\Models\Actividade;
use App\Models\ContactoMensagem;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\MembroPublico;
use App\Models\NewsletterSubscricao;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\Parceiro;
use App\Models\Publicidade;
use App\Models\Revista;
use App\Models\Slide;
use App\Models\Video;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicoController extends Controller
{
    public function inicio()
    {
        // Contagem por grupo do Centro Documental (Legislacao, Circulares, ...)
        $porTipo = Documento::selectRaw('tipo, count(*) as total')->groupBy('tipo')->pluck('total', 'tipo');
        $grupos = collect(config('cda.documentos.grupos'))
            ->map(fn ($g) => $g + ['total' => collect($g['tipos'])->sum(fn ($t) => $porTipo[$t] ?? 0)]);

        return view('publico.inicio', [
            'noticias' => Noticia::where('publicada', true)->orderByDesc('data')->limit(6)->get(),
            'eventos' => Evento::where('activo', true)
                ->where(fn ($query) => $query->whereNull('data_evento')->orWhereDate('data_evento', '>=', today()))
                ->orderByRaw('data_evento IS NULL')
                ->orderBy('data_evento')
                ->limit(5)
                ->get(),
            'emActividade' => $this->emActividade(),
            'slides' => Slide::where('activo', true)->orderBy('ordem')->orderBy('id')->get(),
            'grupos' => $grupos,
            'tiposDocumento' => $porTipo->filter()->sortDesc(),
            'publicacoes' => Documento::orderByDesc('ano')->orderByDesc('id')->limit(5)->get(),
            'revista' => Revista::publicadas()->first(),
            'parceiros' => Parceiro::visiveis()->whereNotNull('logo')->get(),
            'videos' => Video::visiveis()->limit(8)->get(),
            'publicidades' => Publicidade::visiveis()->limit(10)->get(),
        ]);
    }

    /**
     * "CDA em actividade": os eventos mais recentes; se houver menos de tres,
     * completa com as noticias seguintes as do Flash (a partir da setima).
     */
    private function emActividade(): Collection
    {
        $cartoes = Pagina::estaActiva('eventos')
            ? Evento::where('activo', true)->whereNotNull('data_evento')->whereDate('data_evento', '<=', today())
                ->orderByDesc('data_evento')->limit(3)->get()
                ->map(fn (Evento $e) => [
                    'titulo' => $e->titulo, 'url' => route('evento', $e->slug), 'imagem' => $e->imagem,
                    'etiqueta' => 'Evento', 'detalhe' => collect([$e->data_evento?->format('d/m/Y'), $e->local])->filter()->implode(' · '),
                ])
            : collect();

        if ($cartoes->count() < 3 && Pagina::estaActiva('noticias')) {
            $cartoes = $cartoes->concat(
                Noticia::where('publicada', true)->orderByDesc('data')->skip(6)->limit(3 - $cartoes->count())->get()
                    ->map(fn (Noticia $n) => [
                        'titulo' => $n->titulo, 'url' => route('noticia', $n->slug), 'imagem' => $n->imagem,
                        'etiqueta' => $n->categoria ?: 'Notícia', 'detalhe' => $n->data?->format('d/m/Y'),
                    ])
            );
        }

        return $cartoes->values();
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
        $grupo = config('cda.documentos.grupos.'.$request->query('grupo'));
        $q = Documento::query()
            ->when($request->filled('q'), fn ($c) => $c->where('titulo', 'like', '%'.$request->q.'%'))
            ->when($request->filled('tipo'), fn ($c) => $c->where('tipo', $request->tipo))
            ->when(is_array($grupo) && ! $request->filled('tipo'), fn ($c) => $c->whereIn('tipo', $grupo['tipos']))
            ->when($request->filled('ano'), fn ($c) => $c->where('ano', (int) $request->ano))
            ->orderByDesc('ano')->orderBy('titulo');

        return view('publico.documentos', [
            'documentos' => $q->paginate(20)->withQueryString(),
            'tipos' => Documento::whereNotNull('tipo')->distinct()->orderBy('tipo')->pluck('tipo'),
            'grupo' => is_array($grupo) ? $grupo : null,
        ]);
    }

    public function parceiros()
    {
        return view('publico.parceiros', [
            'grupos' => Parceiro::visiveis()->get()->groupBy('categoria'),
        ]);
    }

    public function revista()
    {
        return view('publico.revista', ['revistas' => Revista::publicadas()->get()]);
    }

    /** Abre o PDF da revista no navegador (a capa no site liga para aqui). */
    public function abrirRevista(Revista $revista)
    {
        abort_unless($revista->activa || auth()->user()?->pode('conteudo.gerir'), 404);
        abort_unless(is_file($revista->caminho()), 404);

        return response()->file($revista->caminho(), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($revista->ficheiro).'"',
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
