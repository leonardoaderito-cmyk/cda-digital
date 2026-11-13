@extends('layouts.membro')
@section('titulo', 'Declarações e documentos')
@section('conteudo')
@php $pagas = $quotas->where('estado', 'paga'); @endphp
{{-- Autor: Leonardo (leonardoaderito-cmyk) - documentos emitidos pela CDA em nome do membro --}}
<div class="admin-cabecalho">
    <div><h1>Declarações e documentos</h1><p>Os documentos que a CDA emite em seu nome. Todos têm o selo da CDA, um código único e um código QR para serem confirmados por terceiros.</p></div>
</div>

<div class="membro-documentos">
    <section @class(['membro-documento', 'disponivel' => $regular && ! $membro->is_admin])>
        <img src="{{ \App\Support\Selo::dataUri('Quitação', (string) now()->year) }}" alt="" class="membro-documento-selo">
        <div>
            <h2>Declaração de quitação {{ now()->year }}</h2>
            @if ($regular && ! $membro->is_admin)
                <span class="admin-estado ligado">Disponível para emissão</span>
                <p>Declara formalmente que tem as quotas em dia até {{ now()->year }}. Leva o selo da CDA, código de referência e QR de verificação pública.</p>
                <div class="admin-atalhos">
                    <a href="{{ route('membro.declaracao.descarregar') }}" class="admin-botao"><x-icone nome="download" /> Emitir declaração (PDF)</a>
                    <a href="{{ route('membro.declaracao') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario"><x-icone nome="print" /> Pré-visualizar e imprimir</a>
                </div>
            @else
                <span class="admin-estado alerta">Indisponível</span>
                <p>A declaração fica disponível automaticamente quando todas as quotas estiverem regularizadas, incluindo a de {{ now()->year }}.</p>
                <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-ligacao">Ver quotas por regularizar &rarr;</a></div>
            @endif
        </div>
    </section>

    <section class="membro-documento">
        <x-icone nome="receipt_long" class="membro-documento-icone" />
        <div>
            <h2>Extracto de conta</h2>
            <p>Todos os movimentos: quotas lançadas, pagamentos confirmados e saldo.</p>
            <div class="admin-atalhos">
                <a href="{{ route('membro.extracto.descarregar') }}" class="admin-botao"><x-icone nome="download" /> Extracto (PDF)</a>
                <a href="{{ route('membro.extracto') }}" class="admin-ligacao">Consultar &rarr;</a>
            </div>
        </div>
    </section>

    <section class="membro-documento">
        <x-icone nome="badge" class="membro-documento-icone" />
        <div>
            <h2>Cartão de membro</h2>
            @if ($membro->carteira && ! $membro->is_admin)
                <p>Cartão digital com número de membro e código QR para consulta da situação actual.</p>
                <div class="admin-atalhos"><a href="{{ route('membro.cartao') }}" class="admin-botao admin-botao-secundario">Abrir cartão</a></div>
            @else
                <p>Disponível para contas com número de membro.</p>
            @endif
        </div>
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
                <td class="admin-celula-accoes"><a href="{{ route('membro.comprovativo.descarregar', $q) }}" class="admin-botao admin-botao-s">PDF</a> <a href="{{ route('membro.comprovativo', $q) }}" target="_blank" rel="noopener" class="admin-ligacao">Ver</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
