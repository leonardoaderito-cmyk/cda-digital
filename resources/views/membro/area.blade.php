@extends('layouts.app')
@section('titulo', 'Área de membro')
@section('conteudo')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div><p class="text-sm text-zinc-500">Área do membro</p><h1 class="text-2xl font-medium">{{ $membro->name }}</h1><p class="text-sm text-zinc-600 mt-1">N.º de membro {{ $membro->carteira ?? '—' }} · {{ $membro->email }} @if ($registo) · {{ $registo->empresa }} @endif</p></div>
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="border border-zinc-300 rounded px-4 py-2 text-sm">Terminar sessão</button></form>
</div>

<div class="grid md:grid-cols-3 gap-3 mb-8">
    <div class="bg-white border border-zinc-200 rounded-lg p-5"><span class="text-sm text-zinc-500">Situação financeira</span><strong class="block text-xl font-medium mt-1 {{ $situacao === 'Regularizada' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $situacao }}</strong></div>
    <div class="bg-white border border-zinc-200 rounded-lg p-5"><span class="text-sm text-zinc-500">Total pago</span><strong class="block text-xl font-medium mt-1">{{ number_format($totalPago, 0, ',', '.') }} MT</strong></div>
    <div class="bg-white border border-zinc-200 rounded-lg p-5"><span class="text-sm text-zinc-500">Valor pendente</span><strong class="block text-xl font-medium mt-1">{{ number_format($totalPendente, 0, ',', '.') }} MT</strong></div>
</div>

@if ($regular)
<section class="bg-emerald-50 border border-emerald-200 rounded-lg p-5 mb-8 flex flex-wrap items-center justify-between gap-4">
    <div><h2 class="font-medium text-emerald-900">Quotas em dia</h2><p class="text-sm text-emerald-800 mt-1">A sua declaração de quitação está disponível e pode ser confirmada através do QR.</p></div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('membro.declaracao') }}" target="_blank" class="border border-emerald-700 text-emerald-900 rounded px-4 py-2 text-sm">Visualizar</a>
        <a href="{{ route('membro.declaracao.descarregar') }}" class="bg-emerald-800 text-white rounded px-4 py-2 text-sm">Descarregar declaração PDF</a>
    </div>
</section>
@endif

<h2 class="text-lg font-medium mb-2">Quotas e pagamentos</h2>
<div class="tabela mb-8">
    <div class="tabela-linha tabela-cabecalho"><div class="tabela-celula tabela-celula-estreita">Ano</div><div class="tabela-celula">Estado</div><div class="tabela-celula">Valor</div><div class="tabela-celula">Pagamento</div><div class="tabela-celula">Comprovativo</div></div>
    @forelse ($quotas as $q)
        @php $rotulos = ['pendente'=>'Pendente','paga'=>'Paga','em_atraso'=>'Em atraso','isenta'=>'Isenta']; @endphp
        <div class="tabela-linha">
            <div class="tabela-celula tabela-celula-estreita"><strong class="font-medium">{{ $q->ano }}</strong></div>
            <div class="tabela-celula"><span class="{{ $q->estado === 'paga' || $q->estado === 'isenta' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $rotulos[$q->estado] ?? $q->estado }}</span></div>
            <div class="tabela-celula">{{ number_format($q->valor, 0, ',', '.') }} MT</div>
            <div class="tabela-celula">{{ $q->data_pagamento?->format('d/m/Y') ?: '—' }}<small class="block text-zinc-500">{{ $q->referencia ?: 'Sem referência' }}</small></div>
            <div class="tabela-celula">@if($q->estado === 'paga')<a href="{{ route('membro.comprovativo', $q) }}" target="_blank" class="text-blue-800 hover:underline">Ver</a> · <a href="{{ route('membro.comprovativo.descarregar', $q) }}" class="text-blue-800 hover:underline">PDF</a>@else<span class="text-zinc-400">Disponível após pagamento</span>@endif</div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Ainda não existem quotas registadas na sua conta.</div></div>
    @endforelse
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <section>
        <h2 class="text-lg font-medium mb-2">Requerimentos</h2>
        <div class="tabela mb-4">
            @forelse ($requerimentos as $r)
                <div class="tabela-linha"><div class="tabela-celula"><strong>{{ $r->tipo }}</strong><small class="block text-zinc-500">{{ $r->created_at->format('d/m/Y') }} · {{ str_replace('_', ' ', $r->estado) }}</small><p class="mt-2">{{ $r->descricao }}</p>@if($r->resposta)<p class="mt-2 border-l-2 border-blue-900 pl-3"><strong>Resposta da CDA:</strong> {{ $r->resposta }}</p>@endif</div></div>
            @empty
                <div class="tabela-linha"><div class="tabela-celula">Sem requerimentos.</div></div>
            @endforelse
        </div>
        <form method="POST" action="{{ route('membro.requerimento') }}" class="bg-white border border-zinc-200 rounded-lg p-5 flex flex-col gap-3">@csrf
            <label class="flex flex-col gap-1 text-sm">Tipo<input name="tipo" required class="border border-zinc-300 rounded px-3 py-2"></label>
            <label class="flex flex-col gap-1 text-sm">Descrição<textarea name="descricao" rows="3" class="border border-zinc-300 rounded px-3 py-2"></textarea></label>
            <button class="self-start bg-blue-950 text-white rounded px-4 py-2">Submeter requerimento</button>
        </form>
    </section>

    <section>
        <h2 class="text-lg font-medium mb-2">Segurança da conta</h2>
        <form method="POST" action="{{ route('membro.senha') }}" class="bg-white border border-zinc-200 rounded-lg p-5 flex flex-col gap-3">@csrf
            <p class="text-sm text-zinc-600">Pode alterar a sua senha a qualquer momento. Use pelo menos 8 caracteres.</p>
            <label class="flex flex-col gap-1 text-sm">Senha actual<input type="password" name="password_actual" required autocomplete="current-password" class="border border-zinc-300 rounded px-3 py-2"></label>
            <label class="flex flex-col gap-1 text-sm">Nova senha<input type="password" name="password" required autocomplete="new-password" class="border border-zinc-300 rounded px-3 py-2"></label>
            <label class="flex flex-col gap-1 text-sm">Confirmar nova senha<input type="password" name="password_confirmation" required autocomplete="new-password" class="border border-zinc-300 rounded px-3 py-2"></label>
            <button class="self-start bg-blue-950 text-white rounded px-4 py-2">Alterar senha</button>
        </form>
    </section>
</div>
@endsection
