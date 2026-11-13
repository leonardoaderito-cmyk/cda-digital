@extends('layouts.membro')
@section('titulo', 'Quotas e pagamentos')
@section('conteudo')
@php
    $mt = fn ($v) => number_format($v, 0, ',', '.').' MT';
    $porPagar = $quotas->filter(fn ($q) => in_array($q->estado, ['pendente', 'em_atraso'], true));
@endphp
<div class="admin-cabecalho">
    <div><h1>Quotas e pagamentos</h1><p>Consulte a sua situação financeira, envie o talão de um pagamento e emita comprovativos.</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat {{ $regular ? 'ok' : 'destaque' }}"><span>Situação financeira</span><strong>{{ $situacao }}</strong></div>
    <div class="admin-stat {{ $totalPendente ? 'destaque' : '' }}"><span>Por pagar</span><strong>{{ $mt($totalPendente) }}</strong><small>{{ $porPagar->count() }} {{ $porPagar->count() === 1 ? 'quota' : 'quotas' }}</small></div>
    <div class="admin-stat"><span>Em validação</span><strong>{{ $emValidacao }}</strong><small>talões enviados</small></div>
    <div class="admin-stat"><span>Total pago</span><strong>{{ $mt($totalPago) }}</strong></div>
</div>

@if ($porPagar->isNotEmpty())
<details class="admin-caixa" open>
    <summary>Como pagar</summary>
    <div class="conteudo-texto membro-instrucoes">
        @if ($instrucoes?->conteudo)
            {!! \App\Models\Pagina::limparHtml($instrucoes->conteudo) !!}
        @else
            <p>Contacte a secretaria da CDA para obter os dados de pagamento.</p>
        @endif
    </div>
</details>
@endif

@if ($quotas->isEmpty())
    <div class="admin-vazio"><strong>Ainda não existem quotas registadas na sua conta</strong>Quando a CDA lançar a quota do ano, aparece aqui.</div>
@else
<table class="admin-tabela">
    <thead><tr><th>Ano</th><th>Estado</th><th class="num">Valor</th><th>Pagamento</th><th>Acções</th></tr></thead>
    <tbody>
        @foreach ($quotas as $q)
            @php $devida = in_array($q->estado, ['pendente', 'em_atraso'], true); @endphp
            <tr>
                <td><strong>{{ $q->ano }}</strong></td>
                <td>@include('membro._estado', ['q' => $q])</td>
                <td class="num">{{ $mt($q->valor) }}</td>
                <td>
                    @if ($q->estado === 'paga')
                        {{ $q->data_pagamento?->format('d/m/Y') ?: '—' }}<small>{{ $q->referencia ?: 'Sem referência' }}</small>
                    @elseif ($q->talao_estado === 'em_validacao')
                        Talão enviado em {{ $q->talao_enviado_em?->format('d/m/Y H:i') }}<small>Pagamento de {{ $q->talao_data?->format('d/m/Y') }}@if ($q->talao_referencia) &middot; ref. {{ $q->talao_referencia }}@endif</small>
                    @elseif ($q->talao_estado === 'rejeitado')
                        <strong class="perigo">Talão não aceite</strong><small>{{ $q->talao_nota ?: 'Contacte a secretaria.' }}</small>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if ($q->estado === 'paga')
                        <a href="{{ route('membro.comprovativo.descarregar', $q) }}" class="admin-botao admin-botao-s">Comprovativo (PDF)</a>
                        <a href="{{ route('membro.comprovativo', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Ver</a>
                    @elseif ($devida)
                        @if ($q->talao)<a href="{{ route('membro.talao', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Ver talão enviado</a>@endif
                        <details class="membro-talao" @if (old('quota') == $q->id) open @endif>
                            <summary class="admin-botao admin-botao-s {{ $q->talao_estado === 'em_validacao' ? 'admin-botao-secundario' : '' }}">{{ $q->talao ? 'Substituir talão' : 'Enviar talão' }}</summary>
                            <form method="POST" action="{{ route('membro.talao.enviar', $q) }}" enctype="multipart/form-data" class="admin-form">
                                @csrf
                                <input type="hidden" name="quota" value="{{ $q->id }}">
                                <label>Data do pagamento<input type="date" name="talao_data" max="{{ now()->toDateString() }}" value="{{ old('quota') == $q->id ? old('talao_data') : '' }}" required></label>
                                <label>Referência <small>(opcional)</small><input name="talao_referencia" maxlength="120" value="{{ old('quota') == $q->id ? old('talao_referencia') : '' }}" placeholder="N.º do talão ou da transferência"></label>
                                <label class="total">Talão <small>(PDF, JPG ou PNG, até 5 MB)</small><input type="file" name="talao" accept="application/pdf,image/jpeg,image/png" required style="margin-top:.35rem"></label>
                                <div><button class="admin-botao">Enviar para validação</button></div>
                            </form>
                        </details>
                    @else
                        —
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
