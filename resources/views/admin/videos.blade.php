@extends('layouts.admin')
@section('titulo', 'Vídeos')
@section('conteudo')
{{-- Leonardo (leonardoaderito-cmyk): videos dos eventos; rodam na pagina inicial por esta ordem --}}
<div class="admin-cabecalho">
    <div>
        <h1>Vídeos dos eventos</h1>
        <p>Os vídeos activos rodam na página inicial, pela ordem desta lista: quando um termina, começa o seguinte.</p>
    </div>
    <div class="admin-cabecalho-accoes">
        <a href="{{ route('inicio') }}#videos" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
        <a href="{{ route('admin.videos.novo') }}" class="admin-botao"><x-icone nome="add" /> Novo vídeo</a>
    </div>
</div>

<table class="admin-tabela" id="tabela-videos">
    <thead>
        <tr><th>Ordem</th><th class="admin-col-img"></th><th>Vídeo</th><th>Origem</th><th>Estado</th></tr>
    </thead>
    <tbody>
        @forelse ($videos as $v)
            <tr class="{{ $v->activo ? '' : 'inactiva' }}">
                <td class="admin-ordem">
                    <form method="POST" action="{{ route('admin.videos.mover', $v) }}">@csrf<input type="hidden" name="sentido" value="subir"><button @disabled($loop->first) aria-label="Subir «{{ $v->titulo }}»">&uarr;</button></form>
                    <form method="POST" action="{{ route('admin.videos.mover', $v) }}">@csrf<input type="hidden" name="sentido" value="descer"><button @disabled($loop->last) aria-label="Descer «{{ $v->titulo }}»">&darr;</button></form>
                </td>
                <td class="admin-col-img">@if ($v->imagemCapa())<img src="{{ $v->imagemCapa() }}" alt="">@endif</td>
                <td>
                    <a href="{{ route('admin.videos.editar', $v) }}" class="admin-titulo-linha">{{ $v->titulo }}</a>
                    @if ($v->data)<small> &middot; {{ $v->data->format('d/m/Y') }}</small>@endif
                    <div class="admin-accoes">
                        <a href="{{ route('admin.videos.editar', $v) }}">Editar</a>
                        <form method="POST" action="{{ route('admin.videos.alternar', $v) }}">@csrf<button>{{ $v->activo ? 'Esconder' : 'Mostrar' }}</button></form>
                        <form method="POST" action="{{ route('admin.videos.apagar', $v) }}" onsubmit="return confirm('Remover este vídeo definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>{{ $v->eYoutube() ? 'YouTube' : 'Ficheiro no portal' }}</td>
                <td><span class="admin-estado {{ $v->activo ? 'ligado' : 'desligado' }}">{{ $v->activo ? 'No site' : 'Escondido' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5">Ainda não há vídeos.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
