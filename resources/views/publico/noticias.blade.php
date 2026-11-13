@extends('layouts.app')
@section('titulo', 'Notícias')
@section('conteudo')
@php $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']; @endphp
<h1>Notícias</h1>
<div class="flash flash-largo">
    @forelse ($noticias as $n)
        <article class="flash-item">
            <div class="flash-data" aria-hidden="true">
                <strong>{{ $n->data?->format('d') ?? '--' }}</strong>
                <span>{{ $n->data ? $meses[$n->data->month].' '.$n->data->format('y') : 'CDA' }}</span>
            </div>
            <div>
                <a href="{{ route('noticia', $n->slug) }}">{{ $n->titulo }}</a>
                <small>{{ $n->categoria }}@if ($n->categoria && $n->data) &middot; @endif{{ $n->data?->format('d/m/Y') }}</small>
                <p class="mt-1 text-cda-cinza">{{ \Illuminate\Support\Str::limit($n->resumo, 160) }}</p>
            </div>
        </article>
    @empty
        <p class="py-4 text-cda-cinza">Ainda não há notícias publicadas.</p>
    @endforelse
</div>
<div class="mt-6">{{ $noticias->links() }}</div>
@endsection
