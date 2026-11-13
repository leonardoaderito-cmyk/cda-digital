@extends('layouts.admin')
@section('titulo', 'Notícias')
@section('conteudo')
@php $publicadas = $noticias->where('publicada', true)->count(); @endphp
<div class="admin-cabecalho">
    <div><h1>Notícias</h1><p>{{ $noticias->count() }} {{ $noticias->count() === 1 ? 'notícia' : 'notícias' }}, {{ $publicadas }} {{ $publicadas === 1 ? 'publicada' : 'publicadas' }} no portal.</p></div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('admin.noticias.nova') }}" class="admin-botao"><x-icone nome="add" /> Nova notícia</a></div>
</div>

@if ($noticias->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há notícias</strong>Escreva a primeira notícia para aparecer na página inicial do portal.</div>
@else
@include('admin._separadores', ['tabela' => '#tabela-noticias', 'separadores' => ['' => ['Todas', $noticias->count()], 'publicada' => ['Publicadas', $publicadas], 'oculta' => ['Ocultas', $noticias->count() - $publicadas]]])
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $noticias->count() }} notícias</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-noticias" placeholder="Título ou categoria…"></label>
</div>
<table class="admin-tabela" id="tabela-noticias">
    <thead>
        <tr><th class="admin-col-img"></th><th>Título</th><th>Categoria</th><th>Estado</th><th>Data</th></tr>
    </thead>
    <tbody>
        @foreach ($noticias as $n)
            <tr class="{{ $n->publicada ? '' : 'inactiva' }}" data-grupo="{{ $n->publicada ? 'publicada' : 'oculta' }}">
                <td class="admin-col-img">@if ($n->imagem)<img src="{{ $n->imagem }}" alt="">@endif</td>
                <td>
                    <a href="{{ route('admin.noticias.editar', $n) }}" class="admin-titulo-linha">{{ $n->titulo }}</a>
                    <div class="admin-accoes">
                        <a href="{{ route('admin.noticias.editar', $n) }}">Editar</a>
                        @if ($n->publicada)<a href="{{ route('noticia', $n->slug) }}" target="_blank" rel="noopener">Ver</a>@endif
                        <form method="POST" action="{{ route('admin.noticias.alternar', $n) }}">@csrf<button>{{ $n->publicada ? 'Ocultar' : 'Publicar' }}</button></form>
                        <form method="POST" action="{{ route('admin.noticias.apagar', $n) }}" onsubmit="return confirm('Remover esta notícia definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>{{ $n->categoria ?: '—' }}</td>
                <td><span class="admin-estado {{ $n->publicada ? 'ligado' : 'desligado' }}">{{ $n->publicada ? 'Publicada' : 'Oculta' }}</span></td>
                <td class="admin-col-data">{{ $n->data?->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
