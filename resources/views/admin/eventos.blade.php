@extends('layouts.admin')
@section('titulo', 'Eventos')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Eventos</h1><p>Eventos organizados e promovidos pela CDA, com página própria no portal.</p></div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('admin.eventos.novo') }}" class="admin-botao">Adicionar novo</a></div>
</div>

@if ($eventos->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há eventos</strong>Crie o primeiro evento para aparecer na agenda do portal.</div>
@else
<table class="admin-tabela">
    <thead><tr><th class="admin-col-img"></th><th>Evento</th><th>Local</th><th>Estado</th><th>Data</th></tr></thead>
    <tbody>
        @foreach ($eventos as $evento)
            <tr class="{{ $evento->activo ? '' : 'inactiva' }}">
                <td class="admin-col-img">@if ($evento->imagem)<img src="{{ asset($evento->imagem) }}" alt="">@endif</td>
                <td>
                    <a href="{{ route('admin.eventos.editar', $evento) }}" class="admin-titulo-linha">{{ $evento->titulo }}</a>
                    <div class="admin-accoes">
                        <a href="{{ route('admin.eventos.editar', $evento) }}">Editar</a>
                        @if ($evento->activo)<a href="{{ route('evento', $evento->slug) }}" target="_blank" rel="noopener">Ver</a>@endif
                        <form method="POST" action="{{ route('admin.eventos.alternar', $evento) }}">@csrf<button>{{ $evento->activo ? 'Desactivar' : 'Publicar' }}</button></form>
                        <form method="POST" action="{{ route('admin.eventos.apagar', $evento) }}" onsubmit="return confirm('Remover este evento definitivamente?')">@csrf @method('DELETE')<button class="perigo">Remover</button></form>
                    </div>
                </td>
                <td>{{ $evento->local ?: '—' }}</td>
                <td><span class="admin-estado {{ $evento->activo ? 'ligado' : 'desligado' }}">{{ $evento->activo ? 'Publicado' : 'Desactivado' }}</span></td>
                <td>{{ $evento->data_evento?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
