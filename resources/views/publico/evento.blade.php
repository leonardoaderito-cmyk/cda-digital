@extends('layouts.app')
@section('titulo', $evento->titulo)
@section('conteudo')
<article class="max-w-3xl mx-auto bg-white border border-zinc-200 rounded-lg overflow-hidden">
    @if ($evento->imagem)<img src="{{ asset($evento->imagem) }}" alt="" class="artigo-imagem">@endif
    <div class="p-6 flex flex-col gap-4">
        <div class="text-sm text-zinc-500">{{ $evento->data_evento?->format('d/m/Y') }}@if ($evento->local) &middot; {{ $evento->local }}@endif</div>
        <h1 class="text-3xl font-semibold">{{ $evento->titulo }}</h1>
        @if ($evento->resumo)<p class="text-lg text-zinc-600">{{ $evento->resumo }}</p>@endif
        @foreach ($evento->blocos ?? [] as $bloco)
            @switch($bloco['tipo'])
                @case('titulo')<h2 class="text-2xl font-semibold mt-2">{{ $bloco['conteudo'] }}</h2>@break
                @case('texto')<p class="leading-relaxed whitespace-pre-line">{{ $bloco['conteudo'] }}</p>@break
                @case('imagem')
                    <figure><img src="{{ asset($bloco['conteudo']) }}" alt="{{ $bloco['extra'] ?? '' }}" class="w-full rounded">@if (! empty($bloco['extra']))<figcaption class="text-sm text-zinc-500 mt-1">{{ $bloco['extra'] }}</figcaption>@endif</figure>
                    @break
                @case('destaque')<blockquote class="bg-zinc-100 rounded p-4 text-lg font-medium whitespace-pre-line">{{ $bloco['conteudo'] }}</blockquote>@break
                @case('lista')<ul class="list-disc pl-6 flex flex-col gap-1">@foreach (preg_split('/\r?\n/', $bloco['conteudo']) as $item)@if (trim($item) !== '')<li>{{ trim($item) }}</li>@endif @endforeach</ul>@break
                @case('botao')<a href="{{ $bloco['extra'] }}" class="self-start bg-cda-vermelho text-white font-semibold rounded px-5 py-2 hover:bg-cda-vermelho-escuro">{{ $bloco['conteudo'] }}</a>@break
            @endswitch
        @endforeach
        <a href="{{ route('eventos') }}" class="text-sm text-blue-800 hover:underline">&larr; Todos os eventos</a>
    </div>
</article>
@endsection
