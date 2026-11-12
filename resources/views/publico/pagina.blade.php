@extends('layouts.app')
@section('titulo', $pagina->titulo)
@section('conteudo')
{{-- Autor: Leonardo (leonardoaderito-cmyk) - pagina de texto gerida em Administracao > Paginas --}}
<h1>{{ $pagina->titulo }}</h1>
@if ($pagina->resumo)<p class="text-lg">{{ $pagina->resumo }}</p>@endif
<div class="conteudo-texto">
    {!! \App\Models\Pagina::limparHtml($pagina->conteudo) !!}
</div>
@auth
    @if (auth()->user()->is_admin)
        <p class="mt-8"><a href="{{ route('admin.paginas.editar', $pagina) }}" class="artigo-voltar">Editar esta página &rarr;</a></p>
    @endif
@endauth
@endsection
