@extends('layouts.admin')
@section('titulo', 'Requerimentos')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Requerimentos</h1>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula">Membro</div>
        <div class="tabela-celula" style="flex-grow:2">Pedido</div>
        <div class="tabela-celula" style="flex-grow:3">Resposta</div>
    </div>
    @forelse ($requerimentos as $r)
        <div class="tabela-linha">
            <div class="tabela-celula">{{ $r->user->name }}</div>
            <div class="tabela-celula" style="flex-grow:2"><strong>{{ $r->tipo }}</strong><br>{{ $r->descricao }}</div>
            <div class="tabela-celula" style="flex-grow:3">
                <form method="POST" action="{{ route('admin.requerimentos.responder', $r) }}" class="flex flex-wrap gap-2">@csrf
                    <select name="estado" class="border border-zinc-300 rounded px-2 py-1">
                        @foreach (['em_analise','aprovado','rejeitado'] as $e)<option @selected($r->estado === $e)>{{ $e }}</option>@endforeach
                    </select>
                    <input name="resposta" value="{{ $r->resposta }}" placeholder="Resposta" class="flex-1 min-w-32 border border-zinc-300 rounded px-2 py-1">
                    <button class="bg-blue-950 text-white rounded px-3 py-1">Guardar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Sem requerimentos.</div></div>
    @endforelse
</div>
@endsection
