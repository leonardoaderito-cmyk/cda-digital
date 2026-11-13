@extends('layouts.app')
@section('titulo', 'Verificação do certificado de quitação')
@section('conteudo')
<div class="verificacao-certificado">
    <header class="verificacao-topo">
        <p>Verificação pública CDA Digital</p>
        <h1>Certificado de Quitação</h1>
        <span class="verificacao-selo {{ $regular ? 'valido' : 'invalido' }}">{{ $regular ? 'Certificado válido' : 'Situação não regularizada' }}</span>
    </header>

    <section class="verificacao-corpo">
        <p class="verificacao-explicacao">{{ $regular ? 'Os dados registados na CDA confirmam a quitação das quotas para o ano indicado.' : 'A situação financeira registada já não confirma a quitação para o ano indicado. Contacte a CDA antes de aceitar o documento.' }}</p>
        <dl class="verificacao-dados">
            <div><dt>Membro</dt><dd>{{ $membro->name }}</dd></div>
            <div><dt>Número de membro</dt><dd>{{ $membro->carteira ?: '—' }}</dd></div>
            @if ($entidade)<div><dt>Entidade</dt><dd>{{ $entidade }}</dd></div>@endif
            <div><dt>Ano certificado</dt><dd>{{ $ano }}</dd></div>
            <div><dt>Código do certificado</dt><dd>{{ $codigo }}</dd></div>
            <div><dt>Verificado em</dt><dd>{{ $verificadoEm->format('d/m/Y H:i') }}</dd></div>
        </dl>

        <h2>Quotas consideradas</h2>
        <table class="verificacao-quotas">
            <thead><tr><th>Ano</th><th>Estado</th><th>Pagamento</th></tr></thead>
            <tbody>
                @forelse ($quotas as $quota)
                    <tr><td>{{ $quota->ano }}</td><td>{{ $quota->estado === 'paga' ? 'Paga' : ($quota->estado === 'isenta' ? 'Isenta' : 'Não regularizada') }}</td><td>{{ $quota->data_pagamento?->format('d/m/Y') ?: '—' }}</td></tr>
                @empty
                    <tr><td colspan="3">Sem quotas registadas para este período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    <footer>Consulta protegida por assinatura electrónica da plataforma CDA Digital. Os dados são lidos directamente do registo financeiro da CDA.</footer>
</div>
@endsection
