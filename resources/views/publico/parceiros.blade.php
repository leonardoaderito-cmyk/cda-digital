@extends('layouts.app')
@section('titulo', 'Parceiros')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): parceiros por categoria, geridos em Administracao > Parceiros --}}
<div class="cabecalho-pagina">
    <span class="sobretitulo">Parceiros e instituições</span>
    <h1>Parceiros e ligações relevantes</h1>
    <p>Autoridades, organizações internacionais e instituições com quem a CDA trabalha para o desenvolvimento do comércio e do despacho aduaneiro.</p>
</div>

@forelse (\App\Models\Parceiro::CATEGORIAS as $chave => $categoria)
    @continue(! isset($grupos[$chave]))
    <section class="parceiros-seccao">
        <div class="titulo-seccao"><h2>{{ $categoria }}</h2></div>
        <div class="parceiro-cartoes">
            @foreach ($grupos[$chave] as $p)
                <article class="parceiro-cartao">
                    <div class="parceiro-logo">
                        @if ($p->logo)
                            <img src="{{ $p->logo }}" alt="Logotipo: {{ $p->nome }}" loading="lazy">
                        @else
                            <span class="parceiro-monograma" aria-hidden="true">{{ $p->monograma() }}</span>
                        @endif
                    </div>
                    <div class="parceiro-texto">
                        <h3>{{ $p->nome }}</h3>
                        @if ($p->descricao)<p>{{ $p->descricao }}</p>@endif
                        @if ($p->url)
                            <a href="{{ $p->url }}" target="_blank" rel="noopener" class="parceiro-ligacao">
                                {{ preg_replace('#^https?://(www\.)?|/$#', '', $p->url) }} <x-icone nome="open_in_new" />
                                <span class="sr-only">(abre num separador novo)</span>
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@empty
@endforelse

@if ($grupos->isEmpty())
    <p class="py-6 text-cda-cinza">A lista de parceiros está a ser actualizada.</p>
@endif
@endsection
