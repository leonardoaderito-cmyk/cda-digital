@extends('layouts.app')
@section('titulo', 'Notícias')
@section('conteudo')
@include('admin._menu')
<h1 class="text-2xl font-semibold mb-4">Notícias</h1>
<form method="POST" action="{{ route('admin.noticias.guardar') }}" class="bg-white border border-zinc-200 rounded-lg p-4 mb-6 flex flex-col gap-3">
    @csrf
    <div class="flex flex-wrap gap-3">
        <label class="flex flex-col gap-1 text-sm flex-[3_1_16rem]">Título<input name="titulo" required class="border border-zinc-300 rounded px-3 py-2"></label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-32">Categoria<input name="categoria" class="border border-zinc-300 rounded px-3 py-2"></label>
        <label class="flex flex-col gap-1 text-sm flex-1 min-w-36">Data<input type="date" name="data" value="{{ now()->toDateString() }}" required class="border border-zinc-300 rounded px-3 py-2"></label>
    </div>
    <label class="flex flex-col gap-1 text-sm">Texto<textarea name="texto" rows="5" required class="border border-zinc-300 rounded px-3 py-2"></textarea></label>
    <button class="bg-blue-950 text-white rounded px-4 py-2 self-start">Publicar</button>
</form>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula tabela-celula-estreita">Data</div>
        <div class="tabela-celula" style="flex-grow:4">Título</div>
        <div class="tabela-celula tabela-celula-estreita">Estado</div>
        <div class="tabela-celula" style="flex-grow:1">Acções</div>
    </div>
    @foreach ($noticias as $n)
        <div class="tabela-linha">
            <div class="tabela-celula tabela-celula-estreita">{{ $n->data?->format('d/m/Y') }}</div>
            <div class="tabela-celula" style="flex-grow:4">{{ $n->titulo }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $n->publicada ? 'Publicada' : 'Oculta' }}</div>
            <div class="tabela-celula flex gap-3">
                <form method="POST" action="{{ route('admin.noticias.alternar', $n) }}">@csrf<button class="text-blue-800 hover:underline">{{ $n->publicada ? 'Ocultar' : 'Publicar' }}</button></form>
                <form method="POST" action="{{ route('admin.noticias.apagar', $n) }}" onsubmit="return confirm('Remover esta noticia?')">@csrf @method('DELETE')<button class="text-red-700 hover:underline">Remover</button></form>
            </div>
        </div>
    @endforeach
</div>
@endsection
