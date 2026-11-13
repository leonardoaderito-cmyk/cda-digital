@extends('layouts.app')
@section('titulo', 'Eventos')
@section('conteudo')
<h1 class="text-3xl font-semibold mb-1">Eventos</h1>
<p class="text-zinc-600 mb-5">Os eventos organizados e promovidos pela Câmara dos Despachantes Aduaneiros.</p>
<div class="flex flex-wrap gap-5">
    @forelse ($eventos as $evento)
        <article class="bg-white border border-zinc-200 rounded-lg overflow-hidden flex flex-col flex-[1_1_20rem] max-w-full md:max-w-[calc(33.333%-.85rem)]">
            @if ($evento->imagem)
                <a href="{{ route('evento', $evento->slug) }}"><img src="{{ asset($evento->imagem) }}" alt="" class="w-full h-44 object-cover"></a>
            @else
                <a href="{{ route('evento', $evento->slug) }}" class="h-44 bg-cda-preto text-white flex items-center justify-center text-5xl font-bold" aria-hidden="true">{{ $evento->data_evento?->format('d') ?? 'CDA' }}</a>
            @endif
            <div class="p-4 flex flex-col gap-2 flex-1">
                <div class="text-sm text-zinc-500">{{ $evento->data_evento?->format('d/m/Y') }}@if ($evento->local) &middot; {{ $evento->local }}@endif</div>
                <a href="{{ route('evento', $evento->slug) }}" class="font-semibold text-lg hover:underline">{{ $evento->titulo }}</a>
                @if ($evento->resumo)<p class="text-zinc-600">{{ $evento->resumo }}</p>@endif
                <a href="{{ route('evento', $evento->slug) }}" class="mt-auto text-cda-vermelho font-semibold hover:underline">Ver evento &rarr;</a>
            </div>
        </article>
    @empty
        <p class="text-zinc-600">Não há eventos publicados de momento.</p>
    @endforelse
</div>
<div class="mt-6">{{ $eventos->links() }}</div>
@endsection
