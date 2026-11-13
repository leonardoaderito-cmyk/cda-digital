@extends('layouts.app')
@section('titulo', 'Revista O Despachante')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): edicoes da revista; clicar na capa abre o PDF --}}
@php $ultima = $revistas->first(); @endphp
<div class="cabecalho-pagina">
    <span class="sobretitulo">Publicações</span>
    <h1>Revista O Despachante</h1>
    <p>Publicação oficial da Câmara dos Despachantes Aduaneiros de Moçambique. Clique na capa para abrir a revista.</p>
</div>

@if ($ultima)
    <section class="revista-destaque" aria-label="Edição mais recente">
        <a href="{{ route('revista.abrir', $ultima) }}" target="_blank" rel="noopener" class="revista-capa revista-capa-grande" aria-label="Abrir a revista {{ $ultima->nome() }} (PDF)">
            @include('publico._revista_capa', ['revista' => $ultima])
        </a>
        <div class="revista-destaque-texto">
            <span class="sobretitulo">Edição mais recente</span>
            <h2>{{ $ultima->titulo }}</h2>
            @if ($ultima->edicao || $ultima->data_publicacao)
                <p class="revista-data">{{ collect([$ultima->edicao, $ultima->data_publicacao?->format('d/m/Y')])->filter()->implode(' · ') }}</p>
            @endif
            @if ($ultima->descricao)<p>{{ $ultima->descricao }}</p>@endif
            <a href="{{ route('revista.abrir', $ultima) }}" target="_blank" rel="noopener" class="botao-principal"><x-icone nome="picture_as_pdf" /> Abrir a revista</a>
        </div>
    </section>

    @if ($revistas->count() > 1)
        <section class="mt-10">
            <div class="titulo-seccao"><h2>Edições anteriores</h2></div>
            <div class="revistas-grelha">
                @foreach ($revistas->skip(1) as $r)
                    <a href="{{ route('revista.abrir', $r) }}" target="_blank" rel="noopener" class="revista-cartao" aria-label="Abrir a revista {{ $r->nome() }} (PDF)">
                        <span class="revista-capa">@include('publico._revista_capa', ['revista' => $r])</span>
                        <strong>{{ $r->edicao ?: $r->titulo }}</strong>
                        @if ($r->data_publicacao)<small>{{ $r->data_publicacao->format('d/m/Y') }}</small>@endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@else
    <p class="py-6 text-cda-cinza">Ainda não há edições da revista publicadas.</p>
@endif
@endsection
