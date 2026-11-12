@extends('layouts.app')
@section('titulo', 'Administração')
@section('conteudo')
@include('admin._menu')
<h1 class="text-2xl font-semibold mb-4">Painel de administração</h1>
<div class="flex flex-wrap gap-4">
    @foreach ([['Contas pendentes',$pendentes,'admin.membros'],['Requerimentos em análise',$requerimentos,'admin.requerimentos'],['Mensagens por ler',$mensagens,'admin.mensagens'],['Documentos',$documentos,'admin.documentos'],['Notícias',$noticias,'admin.noticias']] as [$r,$n,$rota])
        <a href="{{ route($rota) }}" class="flex-[1_1_12rem] bg-white border border-zinc-200 rounded-lg p-4 hover:border-blue-900">
            <div class="text-3xl font-bold">{{ $n }}</div><div class="text-sm text-zinc-600">{{ $r }}</div>
        </a>
    @endforeach
</div>
@endsection
