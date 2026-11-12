@extends('layouts.membro')
@section('titulo', 'Documentos')
@section('conteudo')
@php $pagas = $quotas->where('estado', 'paga'); @endphp
<div class="admin-cabecalho">
    <div><h1>Documentos</h1><p>Os documentos que a CDA emite em seu nome. Todos têm um código QR para serem confirmados por terceiros.</p></div>
</div>

<div class="admin-grelha membro-documentos-grelha">
    <section class="admin-caixa membro-certificado {{ $regular ? 'disponivel' : '' }}">
        <h2>Certificado de quitação {{ now()->year }}</h2>
        @if ($regular && ! $membro->is_admin)
            <span class="admin-estado ligado">Disponível para emissão</span>
            <p>Certifica formalmente que tem as quotas em dia até {{ now()->year }}. Inclui código único, assinatura electrónica e QR de verificação pública.</p>
            <div class="admin-atalhos">
                <a href="{{ route('membro.declaracao.descarregar') }}" class="admin-botao">Emitir certificado (PDF)</a>
                <a href="{{ route('membro.declaracao') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Pré-visualizar e imprimir</a>
            </div>
        @else
            <span class="admin-estado alerta">Indisponível</span>
            <p>O certificado fica disponível automaticamente quando todas as quotas estiverem regularizadas, incluindo a de {{ now()->year }}.</p>
            <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-ligacao">Ver quotas por regularizar &rarr;</a></div>
        @endif
    </section>

    <section class="admin-caixa">
        <h2>Cartão de membro</h2>
        @if ($membro->carteira && ! $membro->is_admin)
            <p>Cartão digital com número de membro e código QR para consulta da situação financeira actual.</p>
            <div class="admin-atalhos"><a href="{{ route('membro.cartao') }}" class="admin-botao">Abrir cartão</a></div>
        @else
            <p>Disponível para contas com número de membro.</p>
        @endif
    </section>
</div>

<h2 class="admin-seccao">Comprovativos de pagamento</h2>
<p>Um comprovativo por cada quota paga.</p>
@if ($pagas->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há comprovativos</strong>Aparecem aqui assim que um pagamento for confirmado pela secretaria.</div>
@else
<table class="admin-tabela">
    <thead><tr><th>Quota</th><th>Pago em</th><th class="num">Valor</th><th>Referência</th><th>Comprovativo</th></tr></thead>
    <tbody>
        @foreach ($pagas as $q)
            <tr>
                <td><strong>{{ $q->ano }}</strong></td>
                <td>{{ $q->data_pagamento?->format('d/m/Y') ?: '—' }}</td>
                <td class="num">{{ number_format($q->valor, 0, ',', '.') }} MT</td>
                <td>{{ $q->referencia ?: '—' }}</td>
                <td><a href="{{ route('membro.comprovativo.descarregar', $q) }}" class="admin-botao admin-botao-s">PDF</a> <a href="{{ route('membro.comprovativo', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Ver</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
