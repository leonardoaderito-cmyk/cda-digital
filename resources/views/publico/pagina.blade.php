@extends('layouts.app')
@section('titulo', $pagina->titulo)
@section('conteudo')
{{-- Autor: Leonardo (leonardoaderito-cmyk) - pagina de texto gerida em Administracao > Paginas --}}
@php $sobre = in_array($pagina->slug, ['instituicao', 'historia', 'orgaos', 'estatutos'], true) ? 'A CDA' : null; @endphp
<div class="cabecalho-pagina cabecalho-{{ $pagina->slug }}">
    @if ($sobre)<span class="sobretitulo">{{ $sobre }}</span>@endif
    <h1>{{ $pagina->titulo }}</h1>
    @if ($pagina->resumo)<p>{{ $pagina->resumo }}</p>@endif
</div>
{{-- A classe pagina-<slug> permite um desenho proprio por pagina sem perder a edicao do texto --}}
<div class="conteudo-texto pagina-{{ $pagina->slug }}">
    {!! \App\Models\Pagina::limparHtml($pagina->conteudo) !!}
</div>
@auth
    @if (auth()->user()->is_admin)
        <p class="mt-8"><a href="{{ route('admin.paginas.editar', $pagina) }}" class="artigo-voltar">Editar esta página &rarr;</a></p>
    @endif
@endauth
@endsection
