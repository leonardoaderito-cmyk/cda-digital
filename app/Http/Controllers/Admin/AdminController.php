<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\ContactoMensagem;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Imagem;
use App\Models\Pagina;
use App\Models\NewsletterSubscricao;
use App\Models\Noticia;
use App\Models\Quota;
use App\Models\Requerimento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function painel()
    {
        return view('admin.painel', [
            'pendentes' => User::where('ativo', false)->where('is_admin', false)->count(),
            'requerimentos' => Requerimento::where('estado', 'em_analise')->count(),
            'mensagens' => ContactoMensagem::where('lida', false)->count(),
            'documentos' => Documento::count(),
            'noticias' => Noticia::count(),
            'paginas' => Pagina::count(),
            'paginasInactivas' => Pagina::where('activa', false)->count(),
            'eventos' => Evento::count(),
            'imagens' => Imagem::count(),
            'subscritores' => NewsletterSubscricao::where('activo', true)->count(),
            'ultimasNoticias' => Noticia::orderByDesc('data')->limit(5)->get(),
            'actividade' => Auditoria::orderByDesc('id')->limit(8)->get(),
            'utilizadores' => User::pluck('name', 'id'),
        ]);
    }

    public function membros()
    {
        return view('admin.membros', [
            'membros' => User::where('is_admin', false)->orderBy('ativo')->orderBy('name')->get(),
        ]);
    }

    public function aprovar(User $user): RedirectResponse
    {
        $user->forceFill(['ativo' => true, 'aprovado_em' => now()])->save();
        Quota::firstOrCreate(['user_id' => $user->id, 'ano' => now()->year]);

        return back()->with('estado', 'Conta aprovada.');
    }

    public function desactivar(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);
        $user->forceFill(['ativo' => false])->save();

        return back()->with('estado', 'Conta desactivada.');
    }

    public function quotas()
    {
        return view('admin.quotas', [
            'quotas' => Quota::with('user')->orderByDesc('ano')->orderBy('estado')->get(),
        ]);
    }

    public function actualizarQuota(Request $request, Quota $quota): RedirectResponse
    {
        $dados = $request->validate([
            'estado' => ['required', 'in:pendente,paga,em_atraso,isenta'],
            'valor' => ['nullable', 'integer', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:120'],
        ]);

        $quota->update($dados + [
            'valor' => $dados['valor'] ?? $quota->valor,
            'data_pagamento' => $dados['estado'] === 'paga' ? now() : null,
        ]);

        return back()->with('estado', 'Quota actualizada.');
    }

    public function requerimentos()
    {
        return view('admin.requerimentos', [
            'requerimentos' => Requerimento::with('user')->orderBy('estado')->latest()->get(),
        ]);
    }

    public function responderRequerimento(Request $request, Requerimento $requerimento): RedirectResponse
    {
        $requerimento->update($request->validate([
            'estado' => ['required', 'in:em_analise,aprovado,rejeitado'],
            'resposta' => ['nullable', 'string', 'max:2000'],
        ]));

        return back()->with('estado', 'Requerimento actualizado.');
    }

    public function documentos()
    {
        return view('admin.documentos', ['documentos' => Documento::orderByDesc('updated_at')->paginate(25)]);
    }

    public function guardarDocumento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ficheiro' => ['required', 'file', 'mimes:pdf', 'max:30720'],
            'titulo' => ['required', 'string', 'max:400'],
            'tipo' => ['nullable', 'string', 'max:80'],
            'entidade' => ['nullable', 'string', 'max:160'],
            'ano' => ['nullable', 'integer', 'between:1990,2100'],
        ]);

        $nome = Str::slug($request->file('ficheiro')->getClientOriginalName() ? pathinfo($request->file('ficheiro')->getClientOriginalName(), PATHINFO_FILENAME) : 'documento');
        $final = $nome.'.pdf';
        for ($i = 2; is_file(base_path('acervo/'.$final)); $i++) {
            $final = $nome.'-'.$i.'.pdf';
        }
        $request->file('ficheiro')->move(base_path('acervo'), $final);

        Documento::create([
            'ficheiro' => $final,
            'titulo' => $dados['titulo'],
            'tipo' => $dados['tipo'] ?? null,
            'entidade' => $dados['entidade'] ?? null,
            'ano' => $dados['ano'] ?? null,
            'categoria' => $dados['tipo'] ?? null,
        ]);

        return back()->with('estado', 'Documento publicado.');
    }

    public function apagarDocumento(Documento $documento): RedirectResponse
    {
        $caminho = base_path('acervo/'.basename($documento->ficheiro));
        if (is_file($caminho)) {
            unlink($caminho);
        }
        $documento->delete();

        return back()->with('estado', 'Documento removido.');
    }

    public function noticias()
    {
        return view('admin.noticias', ['noticias' => Noticia::orderByDesc('data')->get()]);
    }

    public function novaNoticia()
    {
        return view('admin.noticia_form', ['noticia' => new Noticia(['data' => now(), 'publicada' => true]), 'imagens' => Imagem::latest()->get()]);
    }

    public function editarNoticia(Noticia $noticia)
    {
        return view('admin.noticia_form', ['noticia' => $noticia, 'imagens' => Imagem::latest()->get()]);
    }

    public function guardarNoticia(Request $request): RedirectResponse
    {
        $dados = $this->validarNoticia($request);

        $base = Str::slug($dados['titulo']) ?: 'noticia';
        $slug = $base;
        for ($i = 2; Noticia::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        $noticia = Noticia::create($dados + ['slug' => $slug, 'publicada' => $request->has('publicada') ? $request->boolean('publicada') : true]);

        return redirect()->route('admin.noticias.editar', $noticia)->with('estado', 'Notícia guardada.');
    }

    public function actualizarNoticia(Request $request, Noticia $noticia): RedirectResponse
    {
        $noticia->update($this->validarNoticia($request, $noticia) + ['publicada' => $request->boolean('publicada')]);

        return back()->with('estado', 'Notícia actualizada.');
    }

    /** Valida a noticia e resolve a imagem de destaque: ficheiro novo, imagem da biblioteca ou nenhuma. */
    private function validarNoticia(Request $request, ?Noticia $noticia = null): array
    {
        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:300'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'data' => ['required', 'date'],
            'texto' => ['required', 'string'],
            'imagem_ficheiro' => ['nullable', ...Imagem::REGRAS],
            'imagem_biblioteca' => ['nullable', 'integer', 'exists:imagens,id'],
        ]);

        $imagem = $noticia?->imagem;
        if ($request->hasFile('imagem_ficheiro')) {
            $imagem = Imagem::guardar($request->file('imagem_ficheiro'), $dados['titulo'])->url();
        } elseif (! empty($dados['imagem_biblioteca'])) {
            $imagem = Imagem::findOrFail($dados['imagem_biblioteca'])->url();
        } elseif ($request->boolean('remover_imagem')) {
            $imagem = null;
        }

        return [
            'titulo' => $dados['titulo'],
            'categoria' => $dados['categoria'] ?? null,
            'data' => $dados['data'],
            'texto' => $dados['texto'],
            'resumo' => Str::limit($dados['texto'], 220),
            'imagem' => $imagem,
        ];
    }

    public function alternarNoticia(Noticia $noticia): RedirectResponse
    {
        $noticia->update(['publicada' => ! $noticia->publicada]);

        return back();
    }

    public function apagarNoticia(Noticia $noticia): RedirectResponse
    {
        $noticia->delete();

        return back()->with('estado', 'Noticia removida.');
    }

    public function mensagens()
    {
        return view('admin.mensagens', ['mensagens' => ContactoMensagem::latest()->get()]);
    }

    public function lerMensagem(ContactoMensagem $mensagem): RedirectResponse
    {
        $mensagem->update(['lida' => true]);

        return back();
    }

    public function newsletter()
    {
        return view('admin.newsletter', [
            'subscricoes' => NewsletterSubscricao::orderByDesc('activo')->latest()->paginate(50),
        ]);
    }

    public function alternarNewsletter(NewsletterSubscricao $newsletterSubscricao): RedirectResponse
    {
        $newsletterSubscricao->update(['activo' => ! $newsletterSubscricao->activo]);

        return back()->with('estado', $newsletterSubscricao->activo ? 'Subscrição reactivada.' : 'Subscrição desactivada.');
    }

    public function exportarNewsletter(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $ficheiro = fopen('php://output', 'wb');
            fwrite($ficheiro, "\xEF\xBB\xBF");
            fputcsv($ficheiro, ['Nome', 'Email', 'Estado', 'Data']);
            NewsletterSubscricao::orderBy('email')->each(function (NewsletterSubscricao $subscricao) use ($ficheiro): void {
                fputcsv($ficheiro, [
                    $subscricao->nome,
                    $subscricao->email,
                    $subscricao->activo ? 'Activo' : 'Inactivo',
                    $subscricao->created_at?->format('Y-m-d H:i:s'),
                ]);
            });
            fclose($ficheiro);
        }, 'newsletter-cda-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
