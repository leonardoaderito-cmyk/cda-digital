@extends('layouts.app')
@section('titulo', 'Membros')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Directório de membros</h1>
<form method="GET" class="flex flex-wrap gap-3 mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, empresa ou carteira" class="flex-1 min-w-48 border border-zinc-300 rounded px-3 py-2">
    <select name="provincia" class="border border-zinc-300 rounded px-3 py-2">
        <option value="">Todas as províncias</option>
        @foreach ($provincias as $p)<option @selected(request('provincia') === $p)>{{ $p }}</option>@endforeach
    </select>
    <button class="bg-blue-950 text-white rounded px-4 py-2">Filtrar</button>
</form>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:2">Nome</div>
        <div class="tabela-celula">Carteira</div>
        <div class="tabela-celula" style="flex-grow:2">Empresa</div>
        <div class="tabela-celula">Província</div>
        <div class="tabela-celula tabela-celula-estreita">Estado</div>
    </div>
    @foreach ($membros as $m)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:2">{{ $m->nome }}</div>
            <div class="tabela-celula">{{ $m->n_carta }}</div>
            <div class="tabela-celula" style="flex-grow:2">{{ $m->empresa }}</div>
            <div class="tabela-celula">{{ $m->provincia }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $m->activo ? 'Activo' : 'Inactivo' }}</div>
        </div>
    @endforeach
</div>
<div class="mt-6">{{ $membros->links() }}</div>
@endsection
