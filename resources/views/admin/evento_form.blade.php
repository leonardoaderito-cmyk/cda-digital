@extends('layouts.admin')
@section('titulo', $evento->exists ? 'Editar evento' : 'Novo evento')
@section('conteudo')
@php
    $blocos = old('blocos', $evento->blocos ?? []);
    $tipos = \App\Models\Evento::TIPOS_BLOCO;
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-semibold">{{ $evento->exists ? 'Editar evento' : 'Novo evento' }}</h1>
    <a href="{{ route('admin.eventos') }}" class="text-blue-800 hover:underline">&larr; Todos os eventos</a>
</div>
<form method="POST" enctype="multipart/form-data" action="{{ $evento->exists ? route('admin.eventos.actualizar', $evento) : route('admin.eventos.guardar') }}" class="flex flex-col gap-5">
    @csrf
    <section class="bg-white border border-zinc-200 rounded-lg p-4 flex flex-col gap-3">
        <h2 class="font-semibold">Dados do evento</h2>
        <div class="flex flex-wrap gap-3">
            <label class="flex flex-col gap-1 text-sm flex-[3_1_16rem]">Título<input name="titulo" required value="{{ old('titulo', $evento->titulo) }}" class="border border-zinc-300 rounded px-3 py-2"></label>
            <label class="flex flex-col gap-1 text-sm flex-1 min-w-36">Data<input type="date" name="data_evento" value="{{ old('data_evento', $evento->data_evento?->toDateString()) }}" class="border border-zinc-300 rounded px-3 py-2"></label>
            <label class="flex flex-col gap-1 text-sm flex-[2_1_12rem]">Local<input name="local" value="{{ old('local', $evento->local) }}" class="border border-zinc-300 rounded px-3 py-2"></label>
        </div>
        <label class="flex flex-col gap-1 text-sm">Resumo (aparece na lista de eventos)<textarea name="resumo" rows="2" maxlength="500" class="border border-zinc-300 rounded px-3 py-2">{{ old('resumo', $evento->resumo) }}</textarea></label>
        <div class="flex flex-wrap items-center gap-4">
            <label class="flex flex-col gap-1 text-sm">Imagem de capa (JPG, PNG ou WebP, até 3 MB)<input type="file" name="imagem_capa" accept="image/jpeg,image/png,image/webp"></label>
            @if ($evento->imagem)<img src="{{ asset($evento->imagem) }}" alt="" class="h-20 rounded border border-zinc-200">@endif
            <label class="flex items-center gap-2"><input type="checkbox" name="activo" value="1" @checked(old('activo', $evento->activo))> Publicar este evento</label>
        </div>
    </section>

    <section class="bg-white border border-zinc-200 rounded-lg p-4 flex flex-col gap-3">
        <h2 class="font-semibold">Página do evento</h2>
        <p class="text-sm text-zinc-600">Desenhe a página juntando blocos. Use as setas para mudar a ordem.</p>
        <div id="blocos" class="flex flex-col gap-3"></div>
        <div class="flex flex-wrap gap-2" id="adicionar">
            @foreach ($tipos as $chave => $rotulo)
                <button type="button" data-tipo="{{ $chave }}" class="border border-zinc-300 rounded px-3 py-1 bg-zinc-50 hover:bg-zinc-100">+ {{ $rotulo }}</button>
            @endforeach
        </div>
    </section>
    <button class="bg-blue-950 text-white rounded px-5 py-2 self-start">Guardar evento</button>
</form>

<template id="modelo-bloco">
    <div class="bloco border border-zinc-300 rounded-lg p-3 flex flex-col gap-2 bg-zinc-50">
        <div class="flex items-center justify-between gap-2">
            <strong data-rotulo></strong>
            <div class="flex gap-1">
                <button type="button" data-acao="subir" class="border border-zinc-300 rounded px-2 bg-white" aria-label="Subir">&uarr;</button>
                <button type="button" data-acao="descer" class="border border-zinc-300 rounded px-2 bg-white" aria-label="Descer">&darr;</button>
                <button type="button" data-acao="remover" class="border border-red-300 text-red-700 rounded px-2 bg-white" aria-label="Remover">&times;</button>
            </div>
        </div>
        <input type="hidden" data-campo="tipo">
        <div data-zona="conteudo"></div>
        <div data-zona="extra"></div>
    </div>
</template>

<script>
// Autor: Leonardo (leonardoaderito-cmyk) - editor de blocos da página do evento
(function () {
    const rotulos = @json($tipos);
    const iniciais = @json(array_values($blocos));
    const lista = document.getElementById('blocos');
    const modelo = document.getElementById('modelo-bloco');
    const classe = 'border border-zinc-300 rounded px-3 py-2 w-full bg-white';

    function criar(tipo, valor) {
        valor = valor || {};
        const no = modelo.content.firstElementChild.cloneNode(true);
        no.dataset.tipo = tipo;
        no.querySelector('[data-rotulo]').textContent = rotulos[tipo];
        no.querySelector('[data-campo="tipo"]').value = tipo;
        const zonaConteudo = no.querySelector('[data-zona="conteudo"]');
        const zonaExtra = no.querySelector('[data-zona="extra"]');
        let campo;

        if (tipo === 'titulo' || tipo === 'botao') {
            campo = document.createElement('input');
        } else if (tipo === 'imagem') {
            campo = document.createElement('input');
            campo.type = 'hidden';
            const ficheiro = document.createElement('input');
            ficheiro.type = 'file';
            ficheiro.accept = 'image/jpeg,image/png,image/webp';
            ficheiro.dataset.ficheiro = '1';
            zonaConteudo.appendChild(ficheiro);
            if (valor.conteudo) {
                const imagem = document.createElement('img');
                imagem.src = '/' + valor.conteudo;
                imagem.className = 'h-24 mt-2 rounded border border-zinc-200';
                zonaConteudo.appendChild(imagem);
            }
        } else {
            campo = document.createElement('textarea');
            campo.rows = tipo === 'lista' ? 5 : 4;
        }

        campo.dataset.conteudo = '1';
        campo.value = valor.conteudo || '';
        if (campo.type !== 'hidden') {
            campo.className = classe;
            campo.placeholder = { titulo: 'Texto do título', texto: 'Escreva o texto', destaque: 'Frase ou aviso em destaque', lista: 'Um item por linha', botao: 'Texto do botão' }[tipo] || '';
        }
        zonaConteudo.prepend(campo);

        if (tipo === 'botao' || tipo === 'imagem') {
            const extra = document.createElement('input');
            extra.dataset.extra = '1';
            extra.className = classe;
            extra.value = valor.extra || '';
            extra.placeholder = tipo === 'botao' ? 'Endereço (https://… ou /pagina)' : 'Legenda (opcional)';
            zonaExtra.appendChild(extra);
        }

        return no;
    }

    function renumerar() {
        [...lista.children].forEach((bloco, indice) => {
            bloco.querySelector('[data-conteudo]').name = `blocos[${indice}][conteudo]`;
            bloco.querySelector('[data-campo="tipo"]').name = `blocos[${indice}][tipo]`;
            const extra = bloco.querySelector('[data-extra]');
            if (extra) extra.name = `blocos[${indice}][extra]`;
            const ficheiro = bloco.querySelector('[data-ficheiro]');
            if (ficheiro) ficheiro.name = `blocos[${indice}][ficheiro]`;
        });
    }

    function adicionar(tipo, valor) {
        lista.appendChild(criar(tipo, valor));
        renumerar();
    }

    lista.addEventListener('click', (evento) => {
        const botao = evento.target.closest('[data-acao]');
        if (!botao) return;
        const bloco = botao.closest('.bloco');
        if (botao.dataset.acao === 'remover') bloco.remove();
        if (botao.dataset.acao === 'subir' && bloco.previousElementSibling) lista.insertBefore(bloco, bloco.previousElementSibling);
        if (botao.dataset.acao === 'descer' && bloco.nextElementSibling) lista.insertBefore(bloco.nextElementSibling, bloco);
        renumerar();
    });

    document.getElementById('adicionar').addEventListener('click', (evento) => {
        const botao = evento.target.closest('[data-tipo]');
        if (botao) adicionar(botao.dataset.tipo);
    });

    iniciais.forEach((bloco) => adicionar(bloco.tipo, bloco));
})();
</script>
@endsection
