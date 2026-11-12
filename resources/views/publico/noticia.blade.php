@extends('layouts.app')
@section('titulo', $noticia->titulo)
@section('conteudo')
<article class="artigo">
    @if ($noticia->categoria)<span class="slide-etiqueta">{{ $noticia->categoria }}</span>@endif
    <h1>{{ $noticia->titulo }}</h1>
    <div class="artigo-data">{{ $noticia->data?->format('d/m/Y') }}</div>
    <div class="leading-relaxed whitespace-pre-line">{{ $noticia->texto }}</div>
    <a href="{{ route('noticias') }}" class="artigo-voltar">&larr; Todas as notícias</a>
</article>
@endsection
