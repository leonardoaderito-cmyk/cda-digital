@extends('layouts.admin')
@section('titulo', 'Membros')
@section('conteudo')
@php
    $gere = auth()->user()->pode('membros.gerir');
    $regulares = $membros->filter(fn ($m) => $m->estaRegular())->pluck('id')->flip();
@endphp
<div class="admin-cabecalho">
    <div><h1>Contas de membros</h1><p>Crie acessos, envie as primeiras credenciais e acompanhe as entradas.</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat"><span>Contas</span><strong>{{ $membros->count() }}</strong></div>
    <div class="admin-stat {{ $membros->where('ativo', false)->count() ? 'destaque' : '' }}"><span>Por aprovar</span><strong>{{ $membros->where('ativo', false)->count() }}</strong></div>
    <div class="admin-stat ok"><span>Situação regularizada</span><strong>{{ $regulares->count() }}</strong></div>
    <div class="admin-stat"><span>Sem senha definida</span><strong>{{ $membros->whereNull('senha_definida_em')->count() }}</strong></div>
    <div class="admin-stat"><span>Membros sem conta</span><strong>{{ $semConta->count() }}</strong><small>na lista da CDA</small></div>
</div>

@if ($gere)
<details class="admin-caixa" @if ($errors->any() || $membros->isEmpty()) open @endif>
    <summary>Criar acesso de membro <small>— envia por email a ligação para definir a senha</small></summary>
    <p>O membro recebe no email o seu número de membro e uma ligação, válida por 72 horas, para definir a sua própria senha.</p>
    <form method="POST" action="{{ route('admin.membros.criar') }}" class="admin-form">
        @csrf
        <label class="largo">Membro da lista CDA
            <select name="membro_publico_id" required>
                <option value="">Seleccionar membro sem conta</option>
                @foreach ($semConta as $registo)<option value="{{ $registo->id }}" @selected(old('membro_publico_id') == $registo->id)>{{ $registo->nome }} — {{ $registo->n_carta }}</option>@endforeach
            </select>
        </label>
        <label>Email registado<input type="email" name="email" value="{{ old('email') }}" required></label>
        <label>Telefone<input name="telefone" value="{{ old('telefone') }}"></label>
        <div><button class="admin-botao">Criar e enviar acesso</button></div>
    </form>
</details>
@endif

@if ($membros->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há contas de membros</strong>Crie o primeiro acesso a partir da lista da CDA.</div>
@else
@php
    $separadores = [
        '' => ['Todas', $membros->count()],
        'aprovar' => ['Por aprovar', $membros->where('ativo', false)->count()],
        'regular' => ['Regularizadas', $regulares->count()],
        'irregular' => ['Não regularizadas', $membros->count() - $regulares->count()],
        'semsenha' => ['Sem senha', $membros->whereNull('senha_definida_em')->count()],
    ];
    $verFinancas = auth()->user()->pode('financeiro.ver');
@endphp
@include('admin._separadores', ['tabela' => '#tabela-membros', 'separadores' => $separadores])
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $membros->count() }} contas</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-membros" placeholder="Nome, número, email…"></label>
</div>
<table class="admin-tabela" id="tabela-membros">
    <thead><tr><th>Membro</th><th>Contacto</th><th>Conta</th><th>Credenciais</th><th>Situação financeira</th></tr></thead>
    <tbody>
        @foreach ($membros as $m)
            <tr class="{{ $m->ativo ? '' : 'destaque' }}" data-grupo="{{ $m->ativo ? '' : 'aprovar' }} {{ isset($regulares[$m->id]) ? 'regular' : 'irregular' }} {{ $m->senha_definida_em ? '' : 'semsenha' }}">
                <td>
                    <strong>{{ $m->name }}</strong>
                    <small>N.º {{ $m->carteira ?: '—' }} &middot; {{ $m->entidade ?: 'Sem entidade' }}</small>
                    @if ($gere)
                    <div class="admin-accoes">
                        <form method="POST" action="{{ route($m->ativo ? 'admin.membros.desactivar' : 'admin.membros.aprovar', $m) }}">@csrf<button class="{{ $m->ativo ? 'perigo' : '' }}">{{ $m->ativo ? 'Desactivar' : 'Aprovar' }}</button></form>
                        <form method="POST" action="{{ route('admin.membros.credenciais', $m) }}">@csrf<button>{{ $m->credenciais_enviadas_em ? 'Reenviar credenciais' : 'Enviar credenciais' }}</button></form>
                    </div>
                    @endif
                </td>
                <td>{{ $m->email }}<small>{{ $m->telefone ?: 'Sem telefone' }}</small></td>
                <td>
                    <span class="admin-estado {{ $m->ativo ? 'ligado' : 'alerta' }}">{{ $m->ativo ? 'Activa' : 'Por aprovar' }}</span>
                    <small>{{ $m->ultimo_login ? 'Última entrada '.$m->ultimo_login->format('d/m/Y H:i') : 'Nunca entrou' }}</small>
                </td>
                <td>
                    @if ($m->senha_definida_em)
                        <span class="admin-estado ligado">Senha definida</span><small>{{ $m->senha_definida_em->format('d/m/Y') }}</small>
                    @elseif ($m->credenciais_enviadas_em)
                        <span class="admin-estado aviso">Convite enviado</span><small>{{ $m->credenciais_enviadas_em->format('d/m/Y H:i') }}</small>
                    @else
                        <span class="admin-estado neutro">Sem convite</span>
                    @endif
                </td>
                <td><span class="admin-estado {{ isset($regulares[$m->id]) ? 'ligado' : 'alerta' }}">{{ isset($regulares[$m->id]) ? 'Regularizada' : 'Não regularizada' }}</span>@if ($verFinancas)<a href="{{ route('admin.membros.extracto', $m) }}" target="_blank" rel="noopener" class="admin-ligacao admin-ligacao-pequena">Ver extracto</a>@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
