@extends('layouts.app')
@section('titulo', 'Verificação de regularidade')
@section('conteudo')
<div class="max-w-2xl mx-auto">
    <div class="text-center mb-6"><p class="text-sm uppercase tracking-wider text-zinc-500">Verificação CDA Digital</p><h1 class="text-2xl font-medium mt-1">Regularidade de membro</h1></div>
    <section class="bg-white border {{ $regular ? 'border-emerald-300' : 'border-amber-300' }} rounded-lg p-7 text-center">
        <div class="inline-flex items-center rounded-full px-4 py-2 {{ $regular ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }} font-medium">{{ $regular ? 'Situação regularizada' : 'Situação não regularizada' }}</div>
        <h2 class="text-xl font-medium mt-5">{{ $membro->name }}</h2>
        <p class="text-zinc-600 mt-1">N.º de membro {{ $membro->carteira }}</p>
        <dl class="grid sm:grid-cols-2 gap-3 text-left mt-7">
            <div class="bg-zinc-50 p-4 rounded"><dt class="text-xs uppercase text-zinc-500">Ano verificado</dt><dd class="mt-1 font-medium">{{ now()->year }}</dd></div>
            <div class="bg-zinc-50 p-4 rounded"><dt class="text-xs uppercase text-zinc-500">Verificado em</dt><dd class="mt-1 font-medium">{{ $verificadoEm->format('d/m/Y H:i') }}</dd></div>
        </dl>
        <p class="text-xs text-zinc-500 mt-6">Código {{ $codigo }} · Consulta assinada electronicamente pela plataforma CDA Digital.</p>
    </section>
</div>
@endsection
