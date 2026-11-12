@extends('layouts.membro')
@section('titulo', 'Cartão de membro')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Cartão de membro</h1><p>Mostre este cartão ou o código QR para confirmar que é membro da CDA e qual é a sua situação.</p></div>
    <div class="admin-cabecalho-accoes"><button type="button" class="admin-botao" onclick="window.print()">Imprimir</button></div>
</div>

<div class="cartao-membro" data-imprimir>
    <div class="cartao-membro-topo">
        <img src="{{ asset('img/cda-logo.jpg') }}" alt="">
        <div><strong>Câmara dos Despachantes Aduaneiros de Moçambique</strong><span>Cartão de membro</span></div>
    </div>
    <div class="cartao-membro-corpo">
        <div class="cartao-membro-dados">
            <span>Nome</span><strong>{{ $membro->name }}</strong>
            <span>Número de membro</span><strong class="cartao-membro-numero">{{ $membro->carteira }}</strong>
            @if ($registo?->empresa || $membro->entidade)<span>Entidade</span><strong>{{ $registo?->empresa ?: $membro->entidade }}</strong>@endif
            @if ($registo?->provincia)<span>Província</span><strong>{{ $registo->provincia }}@if ($registo->delegacao) &middot; Delegação {{ $registo->delegacao }}@endif</strong>@endif
            <span>Situação em {{ now()->format('d/m/Y') }}</span>
            <strong><span class="admin-estado {{ $regular ? 'ligado' : 'alerta' }}">{{ $regular ? 'Quotas regularizadas' : 'Quotas por regularizar' }}</span></strong>
        </div>
        <div class="cartao-membro-qr"><img src="{{ $qrCode }}" alt="Código QR de verificação"><small>Leia o código para confirmar a situação actual</small></div>
    </div>
</div>
<p class="admin-nota">O código QR abre uma página do portal que mostra a situação no momento da leitura; o cartão impresso não substitui essa verificação.</p>
@endsection
