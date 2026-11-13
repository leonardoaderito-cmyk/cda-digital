@extends('layouts.app')
@section('titulo', 'Documentos')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Centro documental</h1>
@if ($grupo)
    <p class="mb-4">A mostrar <strong>{{ $grupo['nome'] }}</strong> ({{ $grupo['descricao'] }}). <a href="{{ route('documentos') }}" class="text-cda-vermelho underline">Ver todos os documentos</a></p>
@endif
<form method="GET" class="flex flex-wrap gap-3 mb-4">
    @if ($grupo)<input type="hidden" name="grupo" value="{{ request('grupo') }}">@endif
    <input name="q" value="{{ request('q') }}" placeholder="Pesquisar título" class="flex-1 min-w-48 border border-zinc-300 rounded px-3 py-2">
    <select name="tipo" class="border border-zinc-300 rounded px-3 py-2">
        <option value="">Todos os tipos</option>
        @foreach ($tipos as $t)<option @selected(request('tipo') === $t)>{{ $t }}</option>@endforeach
    </select>
    <input name="ano" value="{{ request('ano') }}" placeholder="Ano" class="w-24 border border-zinc-300 rounded px-3 py-2">
    <button class="bg-blue-950 text-white rounded px-4 py-2">Filtrar</button>
</form>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:4">Título</div>
        <div class="tabela-celula">Tipo</div>
        <div class="tabela-celula tabela-celula-estreita">Ano</div>
        <div class="tabela-celula tabela-celula-estreita"></div>
    </div>
    @forelse ($documentos as $d)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:4">{{ $d->titulo }}</div>
            <div class="tabela-celula">{{ $d->tipo }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $d->ano }}</div>
            <div class="tabela-celula tabela-celula-estreita"><a class="text-blue-800 hover:underline" target="_blank" href="{{ route('documento.abrir', $d) }}">Abrir</a></div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem resultados.</div></div>
    @endforelse
</div>
<div class="mt-6">{{ $documentos->links() }}</div>
@endsection
