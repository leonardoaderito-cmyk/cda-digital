@extends('layouts.documento')
@section('titulo', 'Declaração de quitação '.$codigo)
@section('referencia-rotulo', 'Declaração n.º')
@php
    $mt = fn ($v) => number_format($v, 0, ',', '.').' MT';
    $estados = ['paga' => 'Paga', 'isenta' => 'Isenta', 'pendente' => 'Por pagar', 'em_atraso' => 'Em atraso'];
@endphp
@section('conteudo')
    {{-- Autor: Leonardo (leonardoaderito-cmyk) - declaracao de quitacao de quotas com selo CDA e verificacao por QR --}}
    <h1 class="titulo">Declaração de Quitação</h1>
    <p class="subtitulo">Quotas regularizadas &middot; Exercício de {{ $ano }}</p>

    <p class="local-data">Maputo, {{ $dataExtenso }}</p>

    <p class="texto">Para os devidos efeitos, a <strong>Câmara dos Despachantes Aduaneiros de Moçambique (CDA)</strong> declara que <strong>{{ mb_strtoupper($membro->name) }}</strong>, titular do número de membro <strong>{{ $membro->carteira }}</strong>@if ($entidade), associado à entidade <strong>{{ $entidade }}</strong>@endif, é membro desta Câmara e tem as quotas registadas <strong>integralmente regularizadas até ao exercício de {{ $ano }}</strong>, encontrando-se em situação financeira regular perante a CDA.</p>
    <p class="texto">Por ser verdade e ter sido solicitada, passa-se a presente declaração, válida para o exercício de {{ $ano }}, que pode ser confirmada a qualquer momento através do código QR ou do código de referência indicados abaixo.</p>

    <table class="quadro" role="presentation"><tr>
        <td><span>Membro</span><strong>{{ $membro->name }}</strong></td>
        <td><span>Número de membro</span><strong>{{ $membro->carteira }}</strong></td>
        <td><span>Situação financeira</span><strong class="verde">Regular</strong></td>
    </tr><tr>
        <td><span>Exercício</span><strong>{{ $ano }}</strong></td>
        <td><span>Quotas regularizadas</span><strong>{{ $quotas->count() }} {{ $quotas->count() === 1 ? 'quota' : 'quotas' }} &middot; {{ $mt($totalPago) }}</strong></td>
        <td><span>Emitida em</span><strong>{{ $emitidoEm->format('d/m/Y \à\s H:i') }}</strong></td>
    </tr></table>

    @if ($quotas->isNotEmpty())
        <p class="seccao">Últimas quotas</p>
        <table class="lista">
            <thead><tr><th>Ano</th><th>Situação</th><th>Data de pagamento</th><th>Referência</th><th class="num">Valor</th></tr></thead>
            <tbody>
                @foreach ($quotas->take(5) as $q)
                    <tr>
                        <td><strong>{{ $q->ano }}</strong></td>
                        <td><span class="etiqueta {{ $q->estado }}">{{ $estados[$q->estado] ?? $q->estado }}</span></td>
                        <td>{{ $q->data_pagamento?->format('d/m/Y') ?: '—' }}</td>
                        <td>{{ $q->referencia ?: '—' }}</td>
                        <td class="num">{{ $mt($q->valor) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="fecho" role="presentation"><tr>
        <td class="fecho-qr"><img src="{{ $qrCode }}" alt="Código QR de verificação"></td>
        <td class="fecho-verificacao">
            <p><strong>VERIFICAÇÃO ELECTRÓNICA</strong></p>
            <p>Leia o código QR com um telemóvel. A página oficial da CDA confirma o membro, o exercício, este código e a situação financeira no momento da leitura.</p>
            <p>Código: <strong>{{ $codigo }}</strong></p>
        </td>
        <td class="fecho-selo"><img src="{{ $selo }}" alt="Selo da CDA: quitação {{ $ano }}"></td>
        <td class="fecho-assinatura">
            <div class="linha"></div>
            <p><strong>A Secretaria-Geral</strong></p>
            <small>Assinatura electrónica &middot; CDA Digital</small>
        </td>
    </tr></table>
@endsection
@section('rodape', 'Declaração emitida electronicamente pela plataforma CDA Digital. Qualquer alteração invalida a correspondência com a verificação pública assinada.')
@section('accoes')
    <a href="{{ route('membro.documentos') }}" class="voltar">&larr; Voltar</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="{{ route('membro.declaracao.descarregar') }}">Descarregar PDF</a>
@endsection
