@extends('layouts.membro')
@section('titulo', 'Segurança')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Segurança</h1><p>Altere a sua senha e veja as últimas entradas na sua conta.</p></div>
</div>

<div class="admin-editor">
    <div>
        <form method="POST" action="{{ route('membro.senha') }}" class="admin-caixa">
            @csrf
            <h2>Alterar senha</h2>
            <p>Use pelo menos 8 caracteres. Não reutilize a senha de outros serviços.</p>
            <div class="admin-form">
                <label class="total">Senha actual<input type="password" name="password_actual" required autocomplete="current-password"></label>
                <label>Nova senha<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Confirmar nova senha<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
                <div class="total"><button class="admin-botao">Alterar senha</button></div>
            </div>
        </form>

        <h2 class="admin-seccao">Últimas entradas</h2>
        <p>Se não reconhecer alguma entrada, altere a senha e contacte a CDA.</p>
        @if ($acessos->isEmpty())
            <div class="admin-vazio"><strong>Sem entradas registadas</strong></div>
        @else
            <table class="admin-tabela">
                <thead><tr><th>Data e hora</th><th>Endereço IP</th></tr></thead>
                <tbody>
                    @foreach ($acessos as $a)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($a->created_at)->format('d/m/Y H:i') }}@if ($loop->first) <span class="admin-estado neutro">Esta sessão ou a mais recente</span>@endif</td><td>{{ $a->ip ?: '—' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>A sua conta</h2>
            <dl class="membro-ficha">
                <dt>Entra com</dt><dd>N.º {{ $membro->carteira ?: '—' }} ou {{ $membro->email }}</dd>
                <dt>Senha definida em</dt><dd>{{ $membro->senha_definida_em?->format('d/m/Y H:i') ?: 'Ainda não definida por si' }}</dd>
                <dt>Última entrada</dt><dd>{{ $membro->ultimo_login?->format('d/m/Y H:i') ?: '—' }}</dd>
            </dl>
            <form method="POST" action="{{ route('logout') }}" class="admin-atalhos">@csrf<button class="admin-botao admin-botao-secundario">Terminar sessão</button></form>
        </section>
    </aside>
</div>
@endsection
