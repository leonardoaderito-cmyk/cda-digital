<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\ContactoMensagem;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\Imagem;
use App\Models\MembroPublico;
use App\Models\NewsletterSubscricao;
use App\Models\Noticia;
use App\Models\Pagina;
use App\Models\Quota;
use App\Models\Requerimento;
use App\Models\User;
use App\Notifications\AcessoAdministrativo;
use App\Notifications\CredenciaisIniciais;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function painel()
    {
        $quotasDoAno = Quota::where('ano', now()->year)->get(['estado', 'valor']);

        return view('admin.painel', [
            'taloes' => Quota::where('talao_estado', 'em_validacao')->count(),
            'membrosActivos' => User::where('is_admin', false)->where('ativo', true)->count(),
            'quotasAno' => [
                'total' => $quotasDoAno->count(),
                'pagas' => $quotasDoAno->whereIn('estado', ['paga', 'isenta'])->count(),
                'recebido' => $quotasDoAno->where('estado', 'paga')->sum('valor'),
                'porReceber' => $quotasDoAno->whereIn('estado', ['pendente', 'em_atraso'])->sum('valor'),
            ],
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
        ]);
    }

    public function utilizadores()
    {
        return view('admin.utilizadores', [
            'utilizadores' => User::where('is_admin', true)->orderBy('name')->get(),
            'actividade' => Auditoria::with('user')->orderByDesc('id')->paginate(30),
            'niveis' => User::NIVEIS_ACESSO,
        ]);
    }

    public function criarUtilizador(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'nivel_acesso' => ['required', Rule::in(array_keys(User::NIVEIS_ACESSO))],
        ]);

        $user = User::create([
            'name' => $dados['name'],
            'email' => Str::lower($dados['email']),
            'password' => Str::random(48),
            'nivel_acesso' => $dados['nivel_acesso'],
        ]);
        $user->forceFill(['is_admin' => true, 'ativo' => true, 'aprovado_em' => now()])->save();
        $this->notificarAcessoAdmin($user);

        return back()->with('estado', 'Utilizador administrativo criado e acesso enviado para '.$user->email.'.');
    }

    public function actualizarUtilizador(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $dados = $request->validate([
            'nivel_acesso' => ['required', Rule::in(array_keys(User::NIVEIS_ACESSO))],
            'ativo' => ['nullable', 'boolean'],
        ]);
        $activo = $request->boolean('ativo');

        if ($user->id === $request->user()->id && ! $activo) {
            return back()->withErrors(['ativo' => 'Não pode desactivar a sua própria conta.']);
        }
        if ($user->nivel_acesso === 'super_admin'
            && ($dados['nivel_acesso'] !== 'super_admin' || ! $activo)
            && User::where('is_admin', true)->where('nivel_acesso', 'super_admin')->where('ativo', true)->count() === 1) {
            return back()->withErrors(['nivel_acesso' => 'Tem de existir pelo menos um administrador geral activo.']);
        }

        $user->forceFill(['nivel_acesso' => $dados['nivel_acesso'], 'ativo' => $activo])->save();

        return back()->with('estado', 'Nível de acesso actualizado para '.$user->name.'.');
    }

    public function enviarAcessoAdmin(User $user): RedirectResponse
    {
        abort_unless($user->is_admin, 404);
        $this->notificarAcessoAdmin($user);

        return back()->with('estado', 'Ligação de acesso reenviada para '.$user->email.'.');
    }

    private function notificarAcessoAdmin(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $user->notify(new AcessoAdministrativo($token));
        $user->forceFill(['credenciais_enviadas_em' => now()])->save();
    }

    public function membros()
    {
        return view('admin.membros', [
            'membros' => User::where('is_admin', false)->orderBy('ativo')->orderBy('name')->get(),
            'semConta' => MembroPublico::where('activo', true)
                ->whereNotIn('n_carta', User::whereNotNull('carteira')->select('carteira'))
                ->orderBy('nome')->get(),
        ]);
    }

    public function criarMembro(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'membro_publico_id' => ['required', 'integer', 'exists:membros_publicos,id'],
            'email' => ['required', 'email', 'max:200', 'unique:users,email'],
            'telefone' => ['nullable', 'string', 'max:40'],
        ]);
        $registo = MembroPublico::findOrFail($dados['membro_publico_id']);
        abort_unless($registo->activo, 422);

        if (User::where('carteira', $registo->n_carta)->exists()) {
            return back()->withErrors(['membro_publico_id' => 'Este membro já tem uma conta.']);
        }

        $user = User::create([
            'name' => $registo->nome,
            'email' => Str::lower($dados['email']),
            'password' => Str::random(48),
            'carteira' => $registo->n_carta,
            'telefone' => $dados['telefone'] ?? null,
            'entidade' => $registo->empresa,
        ]);
        $user->forceFill(['ativo' => true, 'aprovado_em' => now()])->save();
        Quota::firstOrCreate(['user_id' => $user->id, 'ano' => now()->year], ['valor' => Quota::valorDoAno(now()->year)]);
        $this->notificarCredenciais($user);

        return back()->with('estado', 'Conta criada e ligação para definir a senha enviada para '.$user->email.'.');
    }

    public function aprovar(User $user): RedirectResponse
    {
        $user->forceFill(['ativo' => true, 'aprovado_em' => now()])->save();
        Quota::firstOrCreate(['user_id' => $user->id, 'ano' => now()->year], ['valor' => Quota::valorDoAno(now()->year)]);

        return back()->with('estado', 'Conta aprovada.');
    }

    public function desactivar(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);
        $user->forceFill(['ativo' => false])->save();

        return back()->with('estado', 'Conta desactivada.');
    }

    public function enviarCredenciais(User $user): RedirectResponse
    {
        abort_if($user->is_admin, 403);
        $this->notificarCredenciais($user);

        return back()->with('estado', 'Nova ligação para definir a senha enviada para '.$user->email.'.');
    }

    private function notificarCredenciais(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $user->notify(new CredenciaisIniciais($token));
        $user->forceFill(['credenciais_enviadas_em' => now()])->save();
    }

    public function quotas()
    {
        $quotas = Quota::with('user')
            ->orderByRaw("CASE WHEN talao_estado = 'em_validacao' THEN 0 ELSE 1 END")
            ->orderByDesc('ano')->orderBy('estado')->get();

        return view('admin.quotas', [
            'quotas' => $quotas,
            'membros' => User::where('is_admin', false)->where('ativo', true)->orderBy('name')->get(),
            'totalPago' => $quotas->where('estado', 'paga')->sum('valor'),
            'totalPendente' => $quotas->whereIn('estado', ['pendente', 'em_atraso'])->sum('valor'),
            'emAtraso' => $quotas->where('estado', 'em_atraso')->count(),
            'taloesPorValidar' => $quotas->where('talao_estado', 'em_validacao')->count(),
        ]);
    }

    /** Lanca a quota de um ano para todos os membros activos e acerta as que ainda estavam sem valor. */
    public function lancarQuotas(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'ano' => ['required', 'integer', 'between:2000,2100'],
            'valor' => ['required', 'integer', 'min:1'],
        ]);
        $ano = (int) $dados['ano'];
        $valor = (int) $dados['valor'];

        [$criadas, $acertadas, $atrasadas] = DB::transaction(function () use ($ano, $valor, $request) {
            $comQuota = Quota::where('ano', $ano)->pluck('user_id');
            $semQuota = User::where('is_admin', false)->where('ativo', true)->whereNotIn('id', $comQuota)->pluck('id');
            foreach ($semQuota as $id) {
                Quota::create(['user_id' => $id, 'ano' => $ano, 'valor' => $valor, 'estado' => 'pendente']);
            }

            // Quotas por pagar que tinham ficado a 0 (por exemplo, criadas ao aprovar a conta) recebem o valor do ano
            $acertadas = Quota::where('ano', $ano)->where('valor', 0)->whereIn('estado', ['pendente', 'em_atraso'])->update(['valor' => $valor]);

            $atrasadas = $request->boolean('marcar_atraso')
                ? Quota::where('ano', '<', now()->year)->where('estado', 'pendente')->update(['estado' => 'em_atraso'])
                : 0;

            return [$semQuota->count(), $acertadas, $atrasadas];
        });

        $mensagem = "Quota de $ano: $criadas criadas, $acertadas acertadas para ".number_format($valor, 0, ',', '.').' MT.';
        if ($atrasadas) {
            $mensagem .= " $atrasadas quotas de anos anteriores passaram a «em atraso».";
        }

        return back()->with('estado', $mensagem);
    }

    public function criarQuota(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'ano' => [
                'required', 'integer', 'between:2000,2100',
                Rule::unique('quotas')->where(fn ($query) => $query->where('user_id', $request->integer('user_id'))),
            ],
            'valor' => ['required', 'integer', 'min:0'],
            'estado' => ['required', 'in:pendente,paga,em_atraso,isenta'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'data_pagamento' => ['nullable', 'date'],
        ]);
        $membro = User::where('is_admin', false)->findOrFail($dados['user_id']);

        $membro->quotas()->create([
            'ano' => $dados['ano'],
            'valor' => $dados['valor'],
            'estado' => $dados['estado'],
            'referencia' => $dados['referencia'] ?? null,
            'data_pagamento' => $dados['estado'] === 'paga' ? ($dados['data_pagamento'] ?? now()) : null,
        ]);

        return back()->with('estado', 'Quota criada para '.$membro->name.'.');
    }

    public function actualizarQuota(Request $request, Quota $quota): RedirectResponse
    {
        $dados = $request->validate([
            'estado' => ['required', 'in:pendente,paga,em_atraso,isenta'],
            'valor' => ['nullable', 'integer', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'data_pagamento' => ['nullable', 'date'],
        ]);

        $quota->update([...$dados,
            'valor' => $dados['valor'] ?? $quota->valor,
            'data_pagamento' => $dados['estado'] === 'paga'
                ? ($dados['data_pagamento'] ?? $quota->data_pagamento ?? now())
                : null,
            'talao_estado' => $dados['estado'] === 'paga' && $quota->talao
                ? 'validado'
                : $quota->talao_estado,
        ]);

        return back()->with('estado', 'Quota actualizada.');
    }

    /** Confirma ou rejeita o talão enviado pelo membro. */
    public function validarTalao(Request $request, Quota $quota): RedirectResponse
    {
        abort_unless($quota->talao && $quota->talao_estado === 'em_validacao', 404);

        $dados = $request->validate([
            'accao' => ['required', 'in:validar,rejeitar'],
            'nota' => ['required_if:accao,rejeitar', 'nullable', 'string', 'max:300'],
        ]);

        if ($dados['accao'] === 'validar') {
            $quota->update([
                'estado' => 'paga',
                'referencia' => $quota->talao_referencia ?: $quota->referencia,
                'data_pagamento' => $quota->talao_data ?: now(),
                'talao_estado' => 'validado',
                'talao_nota' => null,
            ]);
            Auditoria::registar('admin.talao.validado', 'Quota#'.$quota->id.' ('.$quota->ano.')');

            return back()->with('estado', 'Talão validado e quota marcada como paga.');
        }

        $quota->update([
            'talao_estado' => 'rejeitado',
            'talao_nota' => $dados['nota'],
        ]);
        Auditoria::registar('admin.talao.rejeitado', 'Quota#'.$quota->id.' ('.$quota->ano.')');

        return back()->with('estado', 'Talão rejeitado. O membro pode consultar a nota e enviar outro.');
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

        $texto = Noticia::paraHtml($dados['texto']);
        if (trim(strip_tags($texto, '<img>')) === '') {
            throw ValidationException::withMessages(['texto' => 'Escreva o texto da notícia.']);
        }

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
            'texto' => $texto,
            'resumo' => Noticia::resumoDe($texto),
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
