<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Documento;
use App\Models\Evento;
use App\Models\MembroPublico;
use App\Models\Pagina;
use App\Models\Quota;
use App\Models\User;
use App\Services\PdfService;
use App\Services\QrCodeService;
use App\Support\Selo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// Autor: Leonardo (leonardoaderito-cmyk) - area do membro: resumo, quotas, documentos, requerimentos, dados e seguranca
class MembroController extends Controller
{
    public const TIPOS_REQUERIMENTO = [
        'Declaração', 'Segunda via da carteira profissional', 'Actualização de dados', 'Certidão de inscrição',
        'Pedido de isenção de quota', 'Reclamação', 'Outro',
    ];

    public function area(Request $request)
    {
        $membro = $request->user();
        $dados = $this->contexto($membro);

        return view('membro.area', $dados + [
            'quotaDoAno' => $dados['quotas']->firstWhere('ano', now()->year),
            'requerimentos' => $membro->requerimentos()->latest()->limit(4)->get(),
            'comunicados' => Pagina::estaActiva('documentos')
                ? Documento::whereIn('tipo', ['Circular', 'Comunicado', 'Convocatória'])->orderByDesc('ano')->orderByDesc('id')->limit(5)->get()
                : collect(),
            'eventos' => Pagina::estaActiva('eventos')
                ? Evento::where('activo', true)->whereDate('data_evento', '>=', today())->orderBy('data_evento')->limit(3)->get()
                : collect(),
        ]);
    }

    public function quotas(Request $request)
    {
        return view('membro.quotas', $this->contexto($request->user()) + [
            'instrucoes' => Pagina::where('slug', 'pagamento-de-quotas')->first(),
        ]);
    }

    /** O membro envia o talao de um pagamento; a quota fica "em validacao" ate a secretaria confirmar. */
    public function enviarTalao(Request $request, Quota $quota): RedirectResponse
    {
        abort_unless($quota->user_id === $request->user()->id, 403);
        if (in_array($quota->estado, ['paga', 'isenta'], true)) {
            return back()->withErrors(['talao' => 'Esta quota já está regularizada.']);
        }

        $dados = $request->validate([
            'talao' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'talao_referencia' => ['nullable', 'string', 'max:120'],
            'talao_data' => ['required', 'date', 'before_or_equal:today'],
        ]);

        if ($quota->talao) {
            Storage::disk('local')->delete($quota->talao);
        }
        $quota->update([
            'talao' => $request->file('talao')->store('taloes', 'local'),
            'talao_estado' => 'em_validacao',
            'talao_referencia' => $dados['talao_referencia'] ?? null,
            'talao_data' => $dados['talao_data'],
            'talao_nota' => null,
            'talao_enviado_em' => now(),
        ]);
        Auditoria::registar('membro.talao.enviado', 'Quota#'.$quota->id.' ('.$quota->ano.')');

        return back()->with('estado', 'Talão enviado. A quota de '.$quota->ano.' fica em validação até a secretaria confirmar o pagamento.');
    }

    /** Mostra o talao ao proprio membro ou a quem gere as financas. */
    public function talao(Request $request, Quota $quota)
    {
        abort_unless($quota->user_id === $request->user()->id || $request->user()->pode('financeiro.ver'), 403);
        abort_unless($quota->talao && Storage::disk('local')->exists($quota->talao), 404);

        return Storage::disk('local')->response($quota->talao, 'talao-'.$quota->ano.'.'.pathinfo($quota->talao, PATHINFO_EXTENSION), [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function documentos(Request $request)
    {
        return view('membro.documentos', $this->contexto($request->user()));
    }

    public function cartao(Request $request, QrCodeService $qrCode)
    {
        $membro = $request->user();
        abort_if($membro->is_admin || ! $membro->carteira, 403);

        return view('membro.cartao', $this->contexto($membro) + [
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($membro, now()->year)),
        ]);
    }

    public function requerimentos(Request $request)
    {
        $membro = $request->user();

        return view('membro.requerimentos', $this->contexto($membro) + [
            'requerimentos' => $membro->requerimentos()->latest()->get(),
            'tipos' => self::TIPOS_REQUERIMENTO,
        ]);
    }

    public function requerimento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'string', 'min:2', 'max:80'],
            'descricao' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->requerimentos()->create($dados);

        return back()->with('estado', 'Requerimento submetido. A resposta da CDA aparece nesta página.');
    }

    public function dados(Request $request)
    {
        return view('membro.dados', $this->contexto($request->user()));
    }

    public function actualizarDados(Request $request): RedirectResponse
    {
        $membro = $request->user();
        $dados = $request->validate([
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($membro->id)],
            'telefone' => ['nullable', 'string', 'max:40'],
            'entidade' => ['nullable', 'string', 'max:160'],
            // O email e por onde se recupera a senha: muda-lo exige confirmar a senha actual
            'password_actual' => [Rule::requiredIf(Str::lower((string) $request->input('email')) !== $membro->email), 'nullable', 'current_password'],
        ], ['password_actual.required' => 'Para mudar o email, confirme a sua senha actual.']);

        $emailAntigo = $membro->email;
        $membro->forceFill([
            'email' => Str::lower($dados['email']),
            'telefone' => $dados['telefone'] ?? null,
            'entidade' => $dados['entidade'] ?? null,
        ])->save();
        if ($emailAntigo !== $membro->email) {
            Auditoria::registar('membro.email.alterado', Str::limit($emailAntigo.' → '.$membro->email, 250, ''));
        }

        return back()->with('estado', 'Dados actualizados.');
    }

    public function seguranca(Request $request)
    {
        $membro = $request->user();

        return view('membro.seguranca', $this->contexto($membro) + [
            'acessos' => Auditoria::where('user_id', $membro->id)->where('accao', 'login.ok')->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function alterarSenha(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'password_actual' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($dados['password']),
            'senha_definida_em' => now(),
        ])->save();
        Auditoria::registar('membro.senha.alterada');

        return back()->with('estado', 'Senha alterada com sucesso.');
    }

    public function comprovativo(Request $request, Quota $quota, QrCodeService $qrCode)
    {
        return view('membro.comprovativo', $this->dadosComprovativo($request, $quota, $qrCode) + ['pdf' => false]);
    }

    public function descarregarComprovativo(Request $request, Quota $quota, QrCodeService $qrCode, PdfService $pdf): Response
    {
        $html = view('membro.comprovativo', $this->dadosComprovativo($request, $quota, $qrCode) + ['pdf' => true])->render();

        return $pdf->descarregar($html, 'comprovativo-'.$quota->ano.'-'.$quota->user->carteira.'.pdf');
    }

    public function declaracao(Request $request, QrCodeService $qrCode)
    {
        $membro = $request->user();
        abort_unless(! $membro->is_admin && $membro->estaRegular(), 403);

        return view('membro.declaracao', $this->dadosDeclaracao($membro, $qrCode) + ['pdf' => false]);
    }

    public function descarregarDeclaracao(Request $request, QrCodeService $qrCode, PdfService $pdf): Response
    {
        $membro = $request->user();
        abort_unless(! $membro->is_admin && $membro->estaRegular(), 403);
        $html = view('membro.declaracao', $this->dadosDeclaracao($membro, $qrCode) + ['pdf' => true])->render();
        Auditoria::registar('membro.declaracao.emitida', $this->codigoCertificado($membro, now()->year));

        return $pdf->descarregar($html, 'declaracao-quitacao-'.$membro->carteira.'-'.now()->year.'.pdf');
    }

    /** Extracto de conta corrente: quotas lancadas a debito, pagamentos e isencoes a credito, com o saldo em cada linha. */
    public function extracto(Request $request)
    {
        $membro = $request->user();

        return view('membro.extracto', $this->contexto($membro) + $this->movimentos($membro));
    }

    public function verExtracto(Request $request, QrCodeService $qrCode)
    {
        return view('membro.extracto-documento', $this->dadosExtracto($request->user(), $qrCode) + ['pdf' => false]);
    }

    public function descarregarExtracto(Request $request, QrCodeService $qrCode, PdfService $pdf): Response
    {
        $membro = $request->user();
        $html = view('membro.extracto-documento', $this->dadosExtracto($membro, $qrCode) + ['pdf' => true])->render();

        return $pdf->descarregar($html, 'extracto-'.($membro->carteira ?: $membro->id).'-'.now()->format('Ymd').'.pdf');
    }

    /** A tesouraria consulta (ou descarrega com ?pdf=1) o extracto de um membro. */
    public function extractoMembro(Request $request, User $user, QrCodeService $qrCode, PdfService $pdf)
    {
        abort_if($user->is_admin, 404);
        $dados = $this->dadosExtracto($user, $qrCode);
        if ($request->boolean('pdf')) {
            return $pdf->descarregar(view('membro.extracto-documento', $dados + ['pdf' => true])->render(), 'extracto-'.($user->carteira ?: $user->id).'-'.now()->format('Ymd').'.pdf');
        }

        return view('membro.extracto-documento', $dados + [
            'pdf' => false,
            'urlPdf' => route('admin.membros.extracto', [$user, 'pdf' => 1]),
            'urlVoltar' => route('admin.quotas'),
        ]);
    }

    public function verificarRegularidade(Request $request, User $user)
    {
        abort_if($user->is_admin, 404);
        $ano = (int) $request->query('ano', now()->year);
        abort_unless($ano >= 2000 && $ano <= now()->year + 1, 404);
        $quotas = $user->quotas()->where('ano', '<=', $ano)->orderByDesc('ano')->get();

        return view('membro.verificar-regularidade', [
            'membro' => $user,
            'regular' => $user->estaRegular($ano),
            'ano' => $ano,
            'codigo' => $this->codigoCertificado($user, $ano),
            'verificadoEm' => now(),
            'quotas' => $quotas,
            'entidade' => $user->entidade ?: MembroPublico::where('n_carta', $user->carteira)->value('empresa'),
        ]);
    }

    /** Dados comuns a todos os ecras da area do membro: quotas, totais e situacao financeira. */
    private function contexto(User $membro): array
    {
        $quotas = $membro->quotas()->orderByDesc('ano')->get();
        $regular = $membro->estaRegular();

        return [
            'membro' => $membro,
            'registo' => MembroPublico::where('n_carta', $membro->carteira)->first(),
            'quotas' => $quotas,
            'totalPago' => $quotas->where('estado', 'paga')->sum('valor'),
            'totalPendente' => $quotas->whereIn('estado', ['pendente', 'em_atraso'])->sum('valor'),
            'emValidacao' => $quotas->where('talao_estado', 'em_validacao')->count(),
            'regular' => $regular,
            'situacao' => $regular ? 'Regularizada' : ($quotas->isEmpty() ? 'Sem registo' : ($quotas->contains('estado', 'em_atraso') ? 'Em atraso' : 'Pendente')),
        ];
    }

    private function dadosDeclaracao(User $membro, QrCodeService $qrCode): array
    {
        $ano = now()->year;
        $urlVerificacao = $this->urlVerificacao($membro, $ano);
        $quotas = $membro->quotas()->where('ano', '<=', $ano)->orderByDesc('ano')->get();

        return [
            'membro' => $membro,
            'ano' => $ano,
            'codigo' => $this->codigoCertificado($membro, $ano),
            'qrCode' => $qrCode->dataUri($urlVerificacao),
            'urlVerificacao' => $urlVerificacao,
            'quotas' => $quotas,
            'totalPago' => $quotas->where('estado', 'paga')->sum('valor'),
            'entidade' => $membro->entidade ?: MembroPublico::where('n_carta', $membro->carteira)->value('empresa'),
            'emitidoEm' => now(),
            'dataExtenso' => $this->dataPorExtenso(now()),
            'logo' => $this->logoDataUri(),
            'selo' => Selo::dataUri('Quitação', (string) $ano),
        ];
    }

    private function dadosComprovativo(Request $request, Quota $quota, QrCodeService $qrCode): array
    {
        abort_unless($quota->user_id === $request->user()->id || $request->user()->pode('financeiro.ver'), 403);
        abort_unless($quota->estado === 'paga', 404);
        $quota->load('user');

        return [
            'quota' => $quota,
            'codigo' => sprintf('CDA-Q%06d-%d', $quota->id, $quota->ano),
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($quota->user, $quota->ano)),
            'dataExtenso' => $this->dataPorExtenso($quota->data_pagamento ?? now()),
            'logo' => $this->logoDataUri(),
            'selo' => Selo::dataUri('Pago', (string) $quota->ano),
        ];
    }

    private function dadosExtracto(User $membro, QrCodeService $qrCode): array
    {
        $dados = $this->movimentos($membro);
        $anos = $dados['quotas']->pluck('ano');

        return $dados + [
            'membro' => $membro,
            'codigo' => sprintf('CDA-EX-%s-%06d', now()->format('Ymd'), $membro->id),
            'periodo' => $anos->isEmpty() ? null : ($anos->min() === $anos->max() ? (string) $anos->min() : $anos->min().' a '.$anos->max()),
            'emValidacao' => $dados['quotas']->where('talao_estado', 'em_validacao')->count(),
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($membro, now()->year)),
            'logo' => $this->logoDataUri(),
        ];
    }

    /** Linhas do extracto (por ordem de data), com o saldo acumulado, e os totais. */
    private function movimentos(User $membro): array
    {
        $quotas = $membro->quotas()->orderBy('ano')->get();
        $linhas = collect();
        foreach ($quotas as $q) {
            $lancada = Carbon::create($q->ano, 1, 1);
            $linhas->push(['data' => $lancada, 'ordem' => 0, 'descricao' => 'Quota anual de '.$q->ano, 'referencia' => sprintf('Q%06d', $q->id), 'debito' => $q->valor, 'credito' => 0]);
            if ($q->estado === 'paga') {
                $linhas->push(['data' => $q->data_pagamento ?? $lancada, 'ordem' => 1, 'descricao' => 'Pagamento da quota de '.$q->ano, 'referencia' => $q->referencia, 'debito' => 0, 'credito' => $q->valor]);
            } elseif ($q->estado === 'isenta') {
                $linhas->push(['data' => $lancada, 'ordem' => 1, 'descricao' => 'Isenção da quota de '.$q->ano, 'referencia' => null, 'debito' => 0, 'credito' => $q->valor]);
            }
        }

        $saldo = 0;
        $movimentos = $linhas->sortBy([fn ($a, $b) => $a['data'] <=> $b['data'], fn ($a, $b) => $a['ordem'] <=> $b['ordem']])->values()
            ->map(function ($m) use (&$saldo) {
                $saldo += $m['debito'] - $m['credito'];

                return $m + ['saldo' => $saldo];
            });

        return [
            'quotas' => $quotas,
            'movimentos' => $movimentos,
            'totais' => ['debito' => $linhas->sum('debito'), 'credito' => $linhas->sum('credito'), 'saldo' => $saldo],
        ];
    }

    private function dataPorExtenso(\DateTimeInterface $data): string
    {
        $meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        return $data->format('j').' de '.$meses[(int) $data->format('n')].' de '.$data->format('Y');
    }

    private function urlVerificacao(User $membro, int $ano): string
    {
        return URL::signedRoute('regularidade.verificar', ['user' => $membro->id, 'ano' => $ano]);
    }

    private function codigoCertificado(User $membro, int $ano): string
    {
        return sprintf('CDA-CQ-%d-%06d', $ano, $membro->id);
    }

    private function logoDataUri(): string
    {
        $caminho = public_path('img/cda-logo.jpg');

        return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($caminho));
    }
}
