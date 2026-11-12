@extends('layouts.app')
@section('titulo', 'Documentos')
@section('conteudo')
@include('admin._menu')
<h1 class="text-2xl font-semibold mb-4">Documentos</h1>
<form method="POST" action="{{ route('admin.documentos.guardar') }}" enctype="multipart/form-data" class="bg-white border border-zinc-200 rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
    @csrf
    <label class="flex flex-col gap-1 text-sm flex-[2_1_14rem]">Título<input name="titulo" required class="border border-zinc-300 rounded px-3 py-2"></label>
    <label class="flex flex-col gap-1 text-sm flex-1 min-w-32">Tipo<input name="tipo" class="border border-zinc-300 rounded px-3 py-2"></label>
    <label class="flex flex-col gap-1 text-sm w-24">Ano<input name="ano" class="border border-zinc-300 rounded px-3 py-2"></label>
    <label class="flex flex-col gap-1 text-sm flex-1 min-w-48">PDF<input type="file" name="ficheiro" accept="application/pdf" required></label>
    <button class="bg-blue-950 text-white rounded px-4 py-2">Publicar</button>
</form>
<div class="tabela">
    <div class="tabela-linha tabela-cabecalho">
        <div class="tabela-celula" style="flex-grow:4">Título</div>
        <div class="tabela-celula">Tipo</div>
        <div class="tabela-celula tabela-celula-estreita">Ano</div>
        <div class="tabela-celula tabela-celula-estreita"></div>
    </div>
    @foreach ($documentos as $d)
        <div class="tabela-linha">
            <div class="tabela-celula" style="flex-grow:4">{{ $d->titulo }}</div>
            <div class="tabela-celula">{{ $d->tipo }}</div>
            <div class="tabela-celula tabela-celula-estreita">{{ $d->ano }}</div>
            <div class="tabela-celula tabela-celula-estreita">
                <form method="POST" action="{{ route('admin.documentos.apagar', $d) }}" onsubmit="return confirm('Remover este documento?')">@csrf @method('DELETE')
                    <button class="text-red-700 hover:underline">Remover</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-6">{{ $documentos->links() }}</div>
@endsection
