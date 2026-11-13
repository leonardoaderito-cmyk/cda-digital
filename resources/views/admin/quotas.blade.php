@extends('layouts.admin')
@section('titulo', 'Quotas')
@section('conteudo')
@php
    $gere = auth()->user()->pode('financeiro.gerir');
    $estados = ['pendente' => 'Pendente', 'paga' => 'Paga', 'em_atraso' => 'Em atraso', 'isenta' => 'Isenta'];
    $cores = ['pendente' => 'aviso', 'paga' => 'ligado', 'em_atraso' => 'alerta', 'isenta' => 'neutro'];
    $mt = fn ($v) => number_format($v, 0, ',', '.').' MT';
    $anoLancar = (int) old('ano', now()->year);
    $valorSugerido = \App\Models\Quota::valorDoAno($anoLancar) ?: \App\Models\Quota::valorDoAno($anoLancar - 1);
@endphp
<div class="admin-cabecalho">
    <div><h1>Quotas e situação financeira</h1><p>Lance a quota do ano, registe pagamentos e disponibilize comprovativos na Área do Membro.</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat ok"><span>Total recebido</span><strong>{{ $mt($totalPago) }}</strong></div>
    <div class="admin-stat"><span>Por receber</span><strong>{{ $mt($totalPendente) }}</strong></div>
    <div class="admin-stat {{ $emAtraso ? 'destaque' : '' }}"><span>Quotas em atraso</span><strong>{{ $emAtraso }}</strong></div>
    <div class="admin-stat {{ $taloesPorValidar ? 'destaque' : '' }}"><span>Talões por validar</span><strong>{{ $taloesPorValidar }}</strong></div>
</div>

@if ($gere)
{{-- Autor: Leonardo (leonardoaderito-cmyk) - lancamento da quota anual para todos os membros activos --}}
<details class="admin-caixa" @if ($quotas->isEmpty() || $errors->has('valor') && ! old('user_id')) open @endif>
    <summary>Lançar quota anual <small>— para os {{ $membros->count() }} membros activos</small></summary>
    <p>Cria a quota do ano, como pendente, para os membros activos que ainda não a têm, e dá este valor às quotas por pagar desse ano que estavam a 0. Quotas já pagas, isentas ou com outro valor não são alteradas.</p>
    <form method="POST" action="{{ route('admin.quotas.lancar') }}" class="admin-form" onsubmit="return confirm('Lançar a quota deste ano para todos os membros activos?')">@csrf
        <label>Ano<input name="ano" type="number" min="2000" max="2100" value="{{ $anoLancar }}" required></label>
        <label>Valor da quota (MT)<input name="valor" type="number" min="1" value="{{ old('user_id') ? ($valorSugerido ?: '') : old('valor', $valorSugerido ?: '') }}" required></label>
        <label class="admin-opcao largo"><input type="checkbox" name="marcar_atraso" value="1" @checked(old('marcar_atraso'))> Passar a «em atraso» as quotas pendentes de anos anteriores</label>
        <div><button class="admin-botao">Lançar para todos</button></div>
    </form>
</details>

<details class="admin-caixa" @if (old('user_id')) open @endif>
    <summary>Adicionar quota ou pagamento <small>— de um só membro</small></summary>
    <form method="POST" action="{{ route('admin.quotas.criar') }}" class="admin-form">@csrf
        <label class="largo">Membro<select name="user_id" required><option value="">Seleccionar</option>@foreach ($membros as $m)<option value="{{ $m->id }}" @selected(old('user_id') == $m->id)>{{ $m->name }} — {{ $m->carteira }}</option>@endforeach</select></label>
        <label>Ano<input name="ano" type="number" min="2000" max="2100" value="{{ old('ano', now()->year) }}" required></label>
        <label>Valor (MT)<input name="valor" type="number" min="0" value="{{ old('valor', $valorSugerido ?: 0) }}" required></label>
        <label>Estado<select name="estado">@foreach ($estados as $valor => $rotulo)<option value="{{ $valor }}" @selected(old('estado') === $valor)>{{ $rotulo }}</option>@endforeach</select></label>
        <label>Referência<input name="referencia" value="{{ old('referencia') }}"></label>
        <label>Data do pagamento<input name="data_pagamento" type="date" value="{{ old('data_pagamento') }}"></label>
        <div><button class="admin-botao">Adicionar</button></div>
    </form>
</details>
@endif

@if ($quotas->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há quotas registadas</strong>@if ($gere) Comece por lançar a quota anual para todos os membros. @endif</div>
@else
@php
    $separadores = ['' => ['Todas', $quotas->count()], 'validar' => ['Talões por validar', $taloesPorValidar]];
    foreach ($estados as $valor => $rotulo) {
        $separadores[$valor] = [$rotulo === 'Pendente' ? 'Pendentes' : ($rotulo === 'Paga' ? 'Pagas' : ($rotulo === 'Isenta' ? 'Isentas' : $rotulo)), $quotas->where('estado', $valor)->count()];
    }
@endphp
@include('admin._separadores', ['tabela' => '#tabela-quotas', 'separadores' => $separadores])
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $quotas->count() }} quotas</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-quotas" placeholder="Nome, número, ano, referência…"></label>
</div>
<table class="admin-tabela" id="tabela-quotas">
    <thead><tr><th>Membro</th><th>Ano</th><th>Estado</th><th class="num">Valor</th><th>Pagamento</th><th>Acções</th></tr></thead>
    <tbody>
        @foreach ($quotas as $q)
            @php $porValidar = $q->talao_estado === 'em_validacao'; @endphp
            <tr @class(['destaque' => $porValidar]) data-grupo="{{ $q->estado }}{{ $porValidar ? ' validar' : '' }}">
                <td><strong>{{ $q->user->name }}</strong><small>N.º {{ $q->user->carteira ?: '—' }}</small></td>
                <td><strong>{{ $q->ano }}</strong></td>
                <td>
                    <span class="admin-estado {{ $cores[$q->estado] ?? 'neutro' }}">{{ $estados[$q->estado] ?? $q->estado }}</span>
                    @if ($porValidar)<small>Talão por validar</small>@elseif ($q->talao_estado === 'rejeitado')<small class="perigo">Talão rejeitado</small>@endif
                </td>
                <td class="num">{{ $mt($q->valor) }}</td>
                <td>
                    @if ($porValidar)
                        Talão de {{ $q->talao_data?->format('d/m/Y') ?: '—' }}
                        <small>@if ($q->talao_referencia)Ref. {{ $q->talao_referencia }} &middot; @endif enviado {{ $q->talao_enviado_em?->format('d/m/Y H:i') }}</small>
                    @elseif ($q->estado === 'paga')
                        {{ $q->data_pagamento?->format('d/m/Y') ?: '—' }}<small>{{ $q->referencia ?: 'Sem referência' }}</small>
                    @else
                        <small>{{ $q->referencia ?: '—' }}</small>
                    @endif
                </td>
                <td class="admin-quota-accoes">
                    @if ($porValidar)
                        <a href="{{ route('membro.talao', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Abrir talão</a>
                        @if ($gere)
                            <form method="POST" action="{{ route('admin.quotas.talao', $q) }}" onsubmit="return confirm('Confirmar este pagamento e marcar a quota como paga?')">@csrf<input type="hidden" name="accao" value="validar"><button class="admin-botao admin-botao-s">Validar</button></form>
                            <details class="admin-rejeitar"><summary class="admin-ligacao perigo">Rejeitar</summary><form method="POST" action="{{ route('admin.quotas.talao', $q) }}">@csrf<input type="hidden" name="accao" value="rejeitar"><label>Motivo<input name="nota" maxlength="300" required placeholder="Explique o que deve ser corrigido"></label><button class="admin-botao admin-botao-s">Confirmar rejeição</button></form></details>
                        @endif
                    @endif
                    @if ($q->estado === 'paga')<a href="{{ route('membro.comprovativo', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Comprovativo</a>@endif
                    <a href="{{ route('admin.membros.extracto', $q->user) }}" target="_blank" rel="noopener" class="admin-ligacao">Extracto</a>
                    @if ($gere)
                        <details class="admin-editar-linha">
                            <summary class="admin-ligacao">Editar</summary>
                            <form method="POST" action="{{ route('admin.quotas.actualizar', $q) }}" class="admin-form">@csrf
                                <label>Estado<select name="estado">@foreach ($estados as $e => $rotulo)<option value="{{ $e }}" @selected($q->estado === $e)>{{ $rotulo }}</option>@endforeach</select></label>
                                <label>Valor (MT)<input name="valor" type="number" min="0" value="{{ $q->valor }}"></label>
                                <label>Referência<input name="referencia" value="{{ $q->referencia }}"></label>
                                <label>Data do pagamento<input name="data_pagamento" type="date" value="{{ $q->data_pagamento?->format('Y-m-d') }}"></label>
                                <div><button class="admin-botao admin-botao-s">Guardar</button></div>
                            </form>
                        </details>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
