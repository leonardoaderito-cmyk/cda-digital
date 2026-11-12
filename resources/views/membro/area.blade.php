@extends('layouts.app')
@section('titulo', 'Área de membro')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-1">{{ $membro->name }}</h1>
<p class="text-sm text-zinc-600 mb-6">Carteira {{ $membro->carteira ?? '-' }} &middot; {{ $membro->email }}
    @if ($registo) &middot; {{ $registo->empresa }} @endif</p>

<h2 class="text-lg font-semibold mb-2">Quotas</h2>
<div class="tabela mb-8">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula tabela-celula-estreita">Ano</div>
        <div class="tabela-celula">Estado</div>
        <div class="tabela-celula">Valor (MT)</div>
        <div class="tabela-celula">Referência</div>
    </div>
    @forelse ($quotas as $q)
        <div class="tabela-linha">
            <div class="tabela-celula tabela-celula-estreita">{{ $q->ano }}</div>
            <div class="tabela-celula">{{ $q->estado }}</div>
            <div class="tabela-celula">{{ $q->valor }}</div>
            <div class="tabela-celula">{{ $q->referencia }}</div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem quotas registadas.</div></div>
    @endforelse
</div>

<h2 class="text-lg font-semibold mb-2">Requerimentos</h2>
<div class="tabela mb-6">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula">Tipo</div>
        <div class="tabela-celula" style="flex-grow:2">Descrição</div>
        <div class="tabela-celula">Estado</div>
        <div class="tabela-celula" style="flex-grow:2">Resposta</div>
    </div>
    @forelse ($requerimentos as $r)
        <div class="tabela-linha">
            <div class="tabela-celula">{{ $r->tipo }}</div>
            <div class="tabela-celula" style="flex-grow:2">{{ $r->descricao }}</div>
            <div class="tabela-celula">{{ $r->estado }}</div>
            <div class="tabela-celula" style="flex-grow:2">{{ $r->resposta }}</div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem requerimentos.</div></div>
    @endforelse
</div>

<form method="POST" action="{{ route('membro.requerimento') }}" class="bg-white border border-zinc-200 rounded-lg p-5 flex flex-wrap gap-3 items-end">
    @csrf
    <label class="flex flex-col gap-1 text-sm flex-1 min-w-40">Tipo
        <input name="tipo" required class="border border-zinc-300 rounded px-3 py-2">
    </label>
    <label class="flex flex-col gap-1 text-sm flex-[3_1_16rem]">Descrição
        <input name="descricao" class="border border-zinc-300 rounded px-3 py-2">
    </label>
    <button class="bg-blue-950 text-white rounded px-4 py-2">Submeter requerimento</button>
</form>
@endsection
