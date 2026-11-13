@extends('layouts.documento')
@section('titulo', 'Comprovativo '.$codigo)
@section('referencia-rotulo', 'Comprovativo n.º')
@php $mt = fn ($v) => number_format($v, 0, ',', '.').' MT'; @endphp
@section('conteudo')
    {{-- Autor: Leonardo (leonardoaderito-cmyk) - comprovativo de pagamento de uma quota --}}
    <h1 class="titulo">Comprovativo de Pagamento</h1>
    <p class="subtitulo">Quota anual de {{ $quota->ano }}</p>

    <p class="local-data">Maputo, {{ $dataExtenso }}</p>

    <p class="texto">A <strong>Câmara dos Despachantes Aduaneiros de Moçambique (CDA)</strong> confirma ter recebido de <strong>{{ mb_strtoupper($quota->user->name) }}</strong>, membro n.º <strong>{{ $quota->user->carteira }}</strong>, o pagamento da quota anual referente ao exercício de <strong>{{ $quota->ano }}</strong>, no valor de <strong>{{ $mt($quota->valor) }}</strong>.</p>

    <table class="quadro" role="presentation"><tr>
        <td><span>Membro</span><strong>{{ $quota->user->name }}</strong></td>
        <td><span>Número de membro</span><strong>{{ $quota->user->carteira }}</strong></td>
        <td><span>Quota</span><strong>Exercício de {{ $quota->ano }}</strong></td>
    </tr><tr>
        <td><span>Data do pagamento</span><strong>{{ $quota->data_pagamento?->format('d/m/Y') ?: '—' }}</strong></td>
        <td><span>Referência</span><strong>{{ $quota->referencia ?: '—' }}</strong></td>
        <td><span>Valor pago</span><strong class="verde" style="font-size:15px">{{ $mt($quota->valor) }}</strong></td>
    </tr></table>

    <table class="fecho" role="presentation"><tr>
        <td class="fecho-qr"><img src="{{ $qrCode }}" alt="Código QR de verificação"></td>
        <td class="fecho-verificacao">
            <p><strong>VERIFICAÇÃO ELECTRÓNICA</strong></p>
            <p>Leia o código QR para consultar a situação financeira actual deste membro na plataforma CDA Digital.</p>
            <p>Código: <strong>{{ $codigo }}</strong><br>Emitido em {{ now()->format('d/m/Y H:i') }}</p>
        </td>
        <td class="fecho-selo"><img src="{{ $selo }}" alt="Selo da CDA: pago"></td>
        <td class="fecho-assinatura">
            <div class="linha"></div>
            <p><strong>Tesouraria</strong></p>
            <small>Assinatura electrónica &middot; CDA Digital</small>
        </td>
    </tr></table>
@endsection
@section('accoes')
    <a href="{{ url()->previous() }}" class="voltar">&larr; Voltar</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="{{ route('membro.comprovativo.descarregar', $quota) }}">Descarregar PDF</a>
@endsection
