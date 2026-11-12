@extends('layouts.app')
@section('titulo', 'Eventos')
@section('conteudo')
@include('admin._menu')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-semibold">Eventos</h1>
    <a href="{{ route('admin.eventos.novo') }}" class="bg-blue-950 text-white rounded px-4 py-2">Novo evento</a>
</div>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula tabela-celula-estreita">Data</div>
        <div class="tabela-celula" style="flex-grow:4">Título</div>
        <div class="tabela-celula tabela-celula-estreita">Estado</div>
        <div class="tabela-celula" style="flex-grow:2">Acções</div>
    </div>
    @forelse ($eventos as $evento)
        <div class="tabela-linha">
            <div class="tabela-celula tabela-celula-estreita">{{ $evento->data_evento?->format('d/m/Y') ?? '—' }}</div>
            <div class="tabela-celula" style="flex-grow:4">{{ $evento->titulo }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $evento->activo ? 'Publicado' : 'Desactivado' }}</div>
            <div class="tabela-celula flex flex-wrap gap-3" style="flex-grow:2">
                <a href="{{ route('admin.eventos.editar', $evento) }}" class="text-blue-800 hover:underline">Editar</a>
                @if ($evento->activo)<a href="{{ route('evento', $evento->slug) }}" target="_blank" rel="noopener" class="text-blue-800 hover:underline">Ver</a>@endif
                <form method="POST" action="{{ route('admin.eventos.alternar', $evento) }}">@csrf<button class="text-blue-800 hover:underline">{{ $evento->activo ? 'Desactivar' : 'Publicar' }}</button></form>
                <form method="POST" action="{{ route('admin.eventos.apagar', $evento) }}" onsubmit="return confirm('Remover este evento?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline">Remover</button></form>
            </div>
        </div>
    @empty
        <div class="tabela-linha"><div class="tabela-celula">Ainda não há eventos. Crie o primeiro.</div></div>
    @endforelse
</div>
@endsection
