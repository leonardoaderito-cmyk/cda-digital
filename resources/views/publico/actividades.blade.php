@extends('layouts.app')
@section('titulo', 'Actividades')
@section('conteudo')
<h1 class="text-2xl font-semibold mb-4">Actividades</h1>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula tabela-celula-estreita">Data</div>
        <div class="tabela-celula" style="flex-grow:3">Actividade</div>
        <div class="tabela-celula">Categoria</div>
        <div class="tabela-celula">Local</div>
    </div>
    @foreach ($actividades as $a)
        <div class="tabela-linha">
            <div class="tabela-celula tabela-celula-estreita">{{ $a->data?->format('d/m/Y') }}</div>
            <div class="tabela-celula" style="flex-grow:3">{{ $a->titulo }}</div>
            <div class="tabela-celula">{{ $a->categoria }}</div>
            <div class="tabela-celula">{{ $a->local }}</div>
        </div>
    @endforeach
</div>
<div class="mt-6">{{ $actividades->links() }}</div>
@endsection
