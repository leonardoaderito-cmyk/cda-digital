@extends('layouts.app')
@section('titulo', $noticia->titulo)
@section('conteudo')
<article class="artigo">
    @if ($noticia->categoria)<span class="slide-etiqueta">{{ $noticia->categoria }}</span>@endif
    <h1>{{ $noticia->titulo }}</h1>
    <div class="artigo-data">{{ $noticia->data?->format('d/m/Y') }}</div>
    @if ($noticia->imagem)<img src="{{ \Illuminate\Support\Str::startsWith($noticia->imagem, ['http://', 'https://', '/']) ? $noticia->imagem : asset('storage/'.$noticia->imagem) }}" alt="" class="artigo-imagem">@endif
    <div class="conteudo-texto">{!! $noticia->textoHtml() !!}</div>
    <a href="{{ route('noticias') }}" class="artigo-voltar">&larr; Todas as notícias</a>
</article>
@endsection
