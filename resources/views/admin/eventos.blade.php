@extends('layouts.admin')
@section('titulo', 'Eventos')
@section('conteudo')
@php
    $hoje = today();
    $proximos = $eventos->filter(fn ($e) => $e->activo && $e->data_evento && $e->data_evento->gte($hoje))->count();
    $passados = $eventos->filter(fn ($e) => $e->activo && $e->data_evento && $e->data_evento->lt($hoje))->count();
@endphp
<div class="admin-cabecalho">
    <div><h1>Eventos</h1><p>Eventos organizados e promovidos pela CDA, com página própria no portal.</p></div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('admin.eventos.novo') }}" class="admin-botao"><x-icone nome="add" /> Novo evento</a></div>
</div>

@if ($eventos->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há eventos</strong>Crie o primeiro evento para aparecer na agenda do portal.</div>
@else
@include('admin._separadores', ['tabela' => '#tabela-eventos', 'separadores' => ['' => ['Todos', $eventos->count()], 'proximo' => ['Próximos', $proximos], 'passado' => ['Realizados', $passados], 'desactivado' => ['Desactivados', $eventos->where('activo', false)->count()]]])
<div class="admin-ferramentas">
    <span class="admin-nota" style="margin:0" data-filtro-conta>{{ $eventos->count() }} eventos</span>
    <label class="admin-pesquisa">Procurar <input type="search" data-filtro="#tabela-eventos" placeholder="Título ou local…"></label>
</div>
<table class="admin-tabela" id="tabela-eventos">
    <thead><tr><th class="admin-col-img"></th><th>Evento</th><th>Local</th><th>Estado</th><th>Data</th></tr></thead>
    <tbody>
        @foreach ($eventos as $evento)
            <tr class="{{ $evento->activo ? '' : 'inactiva' }}" data-grupo="{{ ! $evento->activo ? 'desactivado' : (! $evento->data_evento ? '' : ($evento->data_evento->gte($hoje) ? 'proximo' : 'passado')) }}">
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
                <td class="admin-col-data">{{ $evento->data_evento?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
