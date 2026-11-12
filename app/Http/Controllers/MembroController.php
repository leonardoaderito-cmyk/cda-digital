<?php

namespace App\Http\Controllers;

use App\Models\MembroPublico;
use App\Models\Quota;
use App\Models\User;
use App\Services\PdfService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class MembroController extends Controller
{
    public function area(Request $request)
    {
        $membro = $request->user();

        $quotas = $membro->quotas()->orderByDesc('ano')->get();

        return view('membro.area', [
            'membro' => $membro,
            'registo' => MembroPublico::where('n_carta', $membro->carteira)->first(),
            'quotas' => $quotas,
            'requerimentos' => $membro->requerimentos()->latest()->get(),
            'totalPago' => $quotas->where('estado', 'paga')->sum('valor'),
            'totalPendente' => $quotas->whereIn('estado', ['pendente', 'em_atraso'])->sum('valor'),
            'regular' => $membro->estaRegular(),
            'situacao' => $membro->estaRegular() ? 'Regularizada' : ($quotas->isEmpty() ? 'Sem registo' : ($quotas->contains('estado', 'em_atraso') ? 'Em atraso' : 'Pendente')),
        ]);
    }

    public function requerimento(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', 'string', 'min:2', 'max:80'],
            'descricao' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->requerimentos()->create($dados);

        return back()->with('estado', 'Requerimento submetido.');
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

        return back()->with('estado', 'Senha alterada com sucesso.');
    }

    public function comprovativo(Request $request, Quota $quota, QrCodeService $qrCode)
    {
        abort_unless($quota->user_id === $request->user()->id || $request->user()->pode('financeiro.ver'), 403);
        abort_unless($quota->estado === 'paga', 404);

        return view('membro.comprovativo', [
            'quota' => $quota->load('user'),
            'codigo' => sprintf('CDA-Q%06d-%d', $quota->id, $quota->ano),
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($quota->user)),
            'logo' => $this->logoDataUri(),
            'pdf' => false,
        ]);
    }

    public function descarregarComprovativo(Request $request, Quota $quota, QrCodeService $qrCode, PdfService $pdf): Response
    {
        abort_unless($quota->user_id === $request->user()->id || $request->user()->pode('financeiro.ver'), 403);
        abort_unless($quota->estado === 'paga', 404);
        $quota->load('user');
        $codigo = sprintf('CDA-Q%06d-%d', $quota->id, $quota->ano);
        $html = view('membro.comprovativo', [
            'quota' => $quota,
            'codigo' => $codigo,
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($quota->user)),
            'logo' => $this->logoDataUri(),
            'pdf' => true,
        ])->render();

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

        return $pdf->descarregar($html, 'declaracao-quitacao-'.$membro->carteira.'-'.now()->year.'.pdf');
    }

    public function verificarRegularidade(User $user)
    {
        abort_if($user->is_admin, 404);

        return view('membro.verificar-regularidade', [
            'membro' => $user,
            'regular' => $user->estaRegular(),
            'codigo' => sprintf('CDA-R%06d-%d', $user->id, now()->year),
            'verificadoEm' => now(),
        ]);
    }

    private function dadosDeclaracao(User $membro, QrCodeService $qrCode): array
    {
        return [
            'membro' => $membro,
            'ano' => now()->year,
            'codigo' => sprintf('CDA-R%06d-%d', $membro->id, now()->year),
            'qrCode' => $qrCode->dataUri($this->urlVerificacao($membro)),
            'quotas' => $membro->quotas()->where('ano', '<=', now()->year)->orderByDesc('ano')->get(),
            'logo' => $this->logoDataUri(),
        ];
    }

    private function urlVerificacao(User $membro): string
    {
        return URL::signedRoute('regularidade.verificar', ['user' => $membro->id]);
    }

    private function logoDataUri(): string
    {
        $caminho = public_path('img/cda-logo.jpg');

        return 'data:image/jpeg;base64,'.base64_encode(file_get_contents($caminho));
    }
}
