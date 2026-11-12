@extends('layouts.admin')
@section('titulo', 'Quotas')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Quotas</h1>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:2">Membro</div>
        <div class="tabela-celula tabela-celula-estreita">Ano</div>
        <div class="tabela-celula" style="flex-grow:3">Actualizar</div>
    </div>
    @forelse ($quotas as $q)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:2">{{ $q->user->name }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $q->ano }}</div>
            <div class="tabela-celula" style="flex-grow:3">
                <form method="POST" action="{{ route('admin.quotas.actualizar', $q) }}" class="flex flex-wrap gap-2">@csrf
                    <select name="estado" class="border border-zinc-300 rounded px-2 py-1">
                        @foreach (['pendente','paga','em_atraso','isenta'] as $e)<option @selected($q->estado === $e)>{{ $e }}</option>@endforeach
                    </select>
                    <input name="valor" type="number" min="0" value="{{ $q->valor }}" class="w-24 border border-zinc-300 rounded px-2 py-1">
                    <input name="referencia" value="{{ $q->referencia }}" placeholder="Referencia" class="flex-1 min-w-28 border border-zinc-300 rounded px-2 py-1">
                    <button class="bg-blue-950 text-white rounded px-3 py-1">Guardar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem quotas.</div></div>
    @endforelse
</div>
@endsection
