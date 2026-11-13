@extends('layouts.admin')
@section('titulo', $video->exists ? 'Editar vídeo' : 'Novo vídeo')
@section('conteudo')
@php $tipo = old('tipo', $video->tipo ?: 'ficheiro'); @endphp
<div class="admin-cabecalho">
    <h1>{{ $video->exists ? 'Editar vídeo' : 'Adicionar vídeo' }}</h1>
    <a href="{{ route('admin.videos') }}" class="admin-ligacao">&larr; Vídeos</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $video->exists ? route('admin.videos.actualizar', $video) : route('admin.videos.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="250" value="{{ old('titulo', $video->titulo) }}" placeholder="Título do vídeo" class="admin-campo-titulo">

        <label class="admin-rotulo" for="descricao">Descrição <small>(uma frase; opcional)</small></label>
        <textarea id="descricao" name="descricao" rows="2" maxlength="400" class="admin-campo">{{ old('descricao', $video->descricao) }}</textarea>

        <label class="admin-rotulo" for="data">Data do evento</label>
        <input id="data" type="date" name="data" value="{{ old('data', $video->data?->toDateString()) }}" class="admin-campo" style="max-width:14rem">

        <div class="admin-caixa" style="margin-top:1.25rem" data-video-origem>
            <h2>Origem do vídeo</h2>
            <label class="admin-opcao"><input type="radio" name="tipo" value="ficheiro" @checked($tipo === 'ficheiro')> Carregar um ficheiro para o portal <small>(MP4 ou WebM, até 80 MB)</small></label>
            <label class="admin-opcao"><input type="radio" name="tipo" value="youtube" @checked($tipo === 'youtube')> Ligação do YouTube <small>(para vídeos maiores)</small></label>

            <div data-origem="ficheiro" @if ($tipo !== 'ficheiro') hidden @endif>
                @if ($video->urlFicheiro())
                    <video src="{{ $video->urlFicheiro() }}" controls preload="metadata" class="admin-video"></video>
                    <p class="admin-nota">Escolha outro ficheiro só se quiser substituir este.</p>
                @endif
                <label class="admin-rotulo" for="ficheiro">Ficheiro do vídeo</label>
                <input id="ficheiro" type="file" name="ficheiro" accept="video/mp4,video/webm">
            </div>
            <div data-origem="youtube" @if ($tipo !== 'youtube') hidden @endif>
                <label class="admin-rotulo" for="youtube">Ligação do YouTube</label>
                <input id="youtube" name="youtube" maxlength="300" value="{{ old('youtube', $video->youtube_id ? 'https://www.youtube.com/watch?v='.$video->youtube_id : '') }}" class="admin-campo" placeholder="https://www.youtube.com/watch?v=...">
            </div>
        </div>
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activo" value="1" @checked(old('activo', $video->activo))> Visível no site</label>
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $video->exists ? 'Actualizar' : 'Guardar' }}</button>
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Imagem de capa</h2>
            <img @if ($video->capa) src="{{ $video->capa }}" @endif alt="" class="admin-destaque" data-destaque-pre @if (! $video->capa) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($video->capa) hidden @endif>Opcional. Aparece na lista de vídeos; nos vídeos do YouTube usa-se a imagem do próprio YouTube.</p>
            <input type="hidden" name="imagem_biblioteca" data-destaque-id>
            <input type="hidden" name="remover_imagem" value="0" data-destaque-remover>
            <label class="admin-rotulo" for="imagem_ficheiro">Carregar do computador</label>
            <input id="imagem_ficheiro" type="file" name="imagem_ficheiro" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="admin-atalhos">
                <button type="button" class="admin-botao admin-botao-secundario" data-abrir-biblioteca>Escolher da biblioteca</button>
                <button type="button" class="admin-ligacao perigo" data-destaque-limpar>Remover capa</button>
            </div>
        </section>
    </aside>
</form>

{{-- Mostra so o campo da origem escolhida --}}
<script>
    document.querySelectorAll('[data-video-origem] input[name=tipo]').forEach((r) => r.addEventListener('change', () => {
        document.querySelectorAll('[data-origem]').forEach((c) => { c.hidden = c.dataset.origem !== r.value; });
    }));
</script>

@include('admin._biblioteca')
@endsection
