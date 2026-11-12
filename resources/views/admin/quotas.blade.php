@extends('layouts.admin')
@section('titulo', 'Quotas')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-1">Quotas e situação financeira</h1>
<p class="text-sm text-zinc-600 mb-6">Registe pagamentos e disponibilize comprovativos na Área do Membro.</p>

<div class="grid md:grid-cols-3 gap-3 mb-6">
    <div class="bg-white border border-zinc-200 rounded-lg p-4"><span class="text-sm text-zinc-500">Total recebido</span><strong class="block text-2xl mt-1">{{ number_format($totalPago, 0, ',', '.') }} MT</strong></div>
    <div class="bg-white border border-zinc-200 rounded-lg p-4"><span class="text-sm text-zinc-500">Por receber</span><strong class="block text-2xl mt-1">{{ number_format($totalPendente, 0, ',', '.') }} MT</strong></div>
    <div class="bg-white border border-zinc-200 rounded-lg p-4"><span class="text-sm text-zinc-500">Quotas em atraso</span><strong class="block text-2xl mt-1">{{ $emAtraso }}</strong></div>
</div>

@if(auth()->user()->pode('financeiro.gerir'))
{{-- Autor: Leonardo (leonardoaderito-cmyk) - lancamento da quota anual para todos os membros activos --}}
@php $anoLancar = (int) old('ano', now()->year); $valorSugerido = \App\Models\Quota::valorDoAno($anoLancar) ?: \App\Models\Quota::valorDoAno($anoLancar - 1); @endphp
<section class="bg-white border border-zinc-200 rounded-lg p-5 mb-6">
    <h2 class="text-lg font-semibold mb-1">Lançar quota anual</h2>
    <p class="text-sm text-zinc-600 mb-4">Cria a quota do ano, como pendente, para os <strong>{{ $membros->count() }}</strong> membros activos que ainda não a têm, e dá este valor às quotas por pagar desse ano que estavam a 0. Quotas já pagas, isentas ou com outro valor não são alteradas.</p>
    <form method="POST" action="{{ route('admin.quotas.lancar') }}" class="flex flex-wrap gap-3 items-end" onsubmit="return confirm('Lançar a quota deste ano para todos os membros activos?')">@csrf
        <label class="flex flex-col gap-1 text-sm w-28">Ano<input name="ano" type="number" min="2000" max="2100" value="{{ $anoLancar }}" required class="border border-zinc-300 rounded px-2 py-2"></label>
        <label class="flex flex-col gap-1 text-sm w-40">Valor da quota (MT)<input name="valor" type="number" min="1" value="{{ old('valor', $valorSugerido ?: '') }}" required class="border border-zinc-300 rounded px-2 py-2"></label>
        <label class="flex items-center gap-2 text-sm flex-[1_1_18rem] pb-2"><input type="checkbox" name="marcar_atraso" value="1" @checked(old('marcar_atraso'))> Passar a «em atraso» as quotas pendentes de anos anteriores</label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Lançar para todos</button>
    </form>
</section>

<section class="bg-white border border-zinc-200 rounded-lg p-5 mb-8">
    <h2 class="text-lg font-semibold mb-4">Adicionar quota ou pagamento</h2>
    <form method="POST" action="{{ route('admin.quotas.criar') }}" class="flex flex-wrap gap-3 items-end">@csrf
        <label class="flex flex-col gap-1 text-sm flex-[2_1_16rem]">Membro<select name="user_id" required class="border border-zinc-300 rounded px-2 py-2"><option value="">Seleccionar</option>@foreach($membros as $m)<option value="{{ $m->id }}" @selected(old('user_id') == $m->id)>{{ $m->name }} — {{ $m->carteira }}</option>@endforeach</select></label>
        <label class="flex flex-col gap-1 text-sm w-28">Ano<input name="ano" type="number" min="2000" max="2100" value="{{ old('ano', now()->year) }}" required class="border border-zinc-300 rounded px-2 py-2"></label>
        <label class="flex flex-col gap-1 text-sm w-32">Valor (MT)<input name="valor" type="number" min="0" value="{{ old('valor', 0) }}" required class="border border-zinc-300 rounded px-2 py-2"></label>
        <label class="flex flex-col gap-1 text-sm min-w-36">Estado<select name="estado" class="border border-zinc-300 rounded px-2 py-2">@foreach(['pendente'=>'Pendente','paga'=>'Paga','em_atraso'=>'Em atraso','isenta'=>'Isenta'] as $valor=>$rotulo)<option value="{{ $valor }}" @selected(old('estado') === $valor)>{{ $rotulo }}</option>@endforeach</select></label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-40">Referência<input name="referencia" value="{{ old('referencia') }}" class="border border-zinc-300 rounded px-2 py-2"></label>
        <button class="bg-blue-950 text-white rounded px-4 py-2">Adicionar</button>
    </form>
</section>
@endif

<div class="tabela">
    <div class="tabela-linha tabela-cabecalho"><div class="tabela-celula" style="flex-grow:2">Membro</div><div class="tabela-celula tabela-celula-estreita">Ano</div><div class="tabela-celula" style="flex-grow:3">Actualizar</div></div>
    @forelse ($quotas as $q)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:2"><strong>{{ $q->user->name }}</strong><small class="block text-zinc-500">N.º {{ $q->user->carteira }}</small></div>
            <div class="tabela-celula tabela-celula-estreita">{{ $q->ano }}</div>
            <div class="tabela-celula" style="flex-grow:3">
            @if(auth()->user()->pode('financeiro.gerir'))
            <form method="POST" action="{{ route('admin.quotas.actualizar', $q) }}" class="flex flex-wrap gap-2 items-center">@csrf
                <select name="estado" class="border border-zinc-300 rounded px-2 py-1">@foreach (['pendente'=>'Pendente','paga'=>'Paga','em_atraso'=>'Em atraso','isenta'=>'Isenta'] as $e=>$rotulo)<option value="{{ $e }}" @selected($q->estado === $e)>{{ $rotulo }}</option>@endforeach</select>
                <input name="valor" type="number" min="0" value="{{ $q->valor }}" class="w-28 border border-zinc-300 rounded px-2 py-1" aria-label="Valor">
                <input name="referencia" value="{{ $q->referencia }}" placeholder="Referência" class="flex-1 min-w-32 border border-zinc-300 rounded px-2 py-1">
                <input name="data_pagamento" type="date" value="{{ $q->data_pagamento?->format('Y-m-d') }}" class="border border-zinc-300 rounded px-2 py-1" aria-label="Data do pagamento">
                <button class="bg-blue-950 text-white rounded px-3 py-1">Guardar</button>
                @if($q->estado === 'paga')<a href="{{ route('membro.comprovativo', $q) }}" target="_blank" class="text-blue-800 hover:underline">Comprovativo</a>@endif
            </form>
            @else
                <span>{{ ucfirst(str_replace('_', ' ', $q->estado)) }} · {{ number_format($q->valor, 0, ',', '.') }} MT · {{ $q->referencia ?: 'Sem referência' }}</span>
                @if($q->estado === 'paga') · <a href="{{ route('membro.comprovativo', $q) }}" target="_blank" class="text-blue-800 hover:underline">Comprovativo</a>@endif
            @endif
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem quotas registadas.</div></div>
    @endforelse
</div>
@endsection
