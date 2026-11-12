@extends('layouts.admin')
@section('titulo', $noticia->exists ? 'Editar notícia' : 'Nova notícia')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>{{ $noticia->exists ? 'Editar notícia' : 'Adicionar nova notícia' }}</h1>
    <a href="{{ route('admin.noticias') }}" class="admin-ligacao">&larr; Todas as notícias</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $noticia->exists ? route('admin.noticias.actualizar', $noticia) : route('admin.noticias.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="300" value="{{ old('titulo', $noticia->titulo) }}" placeholder="Título da notícia" class="admin-campo-titulo">
        <label class="admin-rotulo" for="texto">Texto</label>
        <textarea id="texto" name="texto" rows="18" required class="admin-campo">{{ old('texto', $noticia->texto) }}</textarea>
        <p class="admin-nota">As quebras de linha são respeitadas na página da notícia. O resumo da lista é criado a partir do início do texto.</p>
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <input type="hidden" name="publicada" value="0">
            <label class="admin-opcao"><input type="checkbox" name="publicada" value="1" @checked(old('publicada', $noticia->publicada))> Publicada <small>(visível no portal)</small></label>
            <label class="admin-rotulo" for="data">Data</label>
            <input id="data" type="date" name="data" required value="{{ old('data', $noticia->data?->toDateString()) }}" class="admin-campo">
            <label class="admin-rotulo" for="categoria">Categoria</label>
            <input id="categoria" name="categoria" maxlength="80" value="{{ old('categoria', $noticia->categoria) }}" class="admin-campo" placeholder="Ex.: Cooperação">
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $noticia->exists ? 'Actualizar' : 'Publicar' }}</button>
                @if ($noticia->exists && $noticia->publicada)<a href="{{ route('noticia', $noticia->slug) }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver notícia</a>@endif
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Imagem de destaque</h2>
            <img @if ($noticia->imagem) src="{{ $noticia->imagem }}" @endif alt="" class="admin-destaque" data-destaque-pre @if (! $noticia->imagem) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($noticia->imagem) hidden @endif>Sem imagem. Aparece no carrossel e na lista da página inicial.</p>
            <input type="hidden" name="imagem_biblioteca" data-destaque-id>
            <input type="hidden" name="remover_imagem" value="0" data-destaque-remover>
            <label class="admin-rotulo" for="imagem_ficheiro">Carregar do computador</label>
            <input id="imagem_ficheiro" type="file" name="imagem_ficheiro" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="admin-atalhos">
                <button type="button" class="admin-botao admin-botao-secundario" data-abrir-biblioteca>Escolher da biblioteca</button>
                <button type="button" class="admin-ligacao perigo" data-destaque-limpar>Remover imagem</button>
            </div>
        </section>
    </aside>
</form>

@include('admin._biblioteca')
@endsection
