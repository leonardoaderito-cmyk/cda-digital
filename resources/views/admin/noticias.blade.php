@extends('layouts.admin')
@section('titulo', 'Notícias')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>Notícias</h1>
    <a href="{{ route('admin.noticias.nova') }}" class="admin-botao">Adicionar nova</a>
</div>

<table class="admin-tabela">
    <thead>
        <tr><th class="admin-col-img"></th><th>Título</th><th>Categoria</th><th>Estado</th><th>Data</th></tr>
    </thead>
    <tbody>
        @forelse ($noticias as $n)
            <tr class="{{ $n->publicada ? '' : 'inactiva' }}">
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
                <td>{{ $n->data?->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5">Ainda não há notícias.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
