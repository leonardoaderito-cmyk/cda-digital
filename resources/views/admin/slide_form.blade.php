@extends('layouts.admin')
@section('titulo', $slide->exists ? 'Editar slide' : 'Novo slide')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>{{ $slide->exists ? 'Editar slide' : 'Adicionar slide' }}</h1>
    <a href="{{ route('admin.slides') }}" class="admin-ligacao">&larr; Carrossel</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('admin.slides.actualizar', $slide) : route('admin.slides.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="250" value="{{ old('titulo', $slide->titulo) }}" placeholder="Título do slide" class="admin-campo-titulo">

        <label class="admin-rotulo" for="etiqueta">Etiqueta <small>(pequena faixa vermelha por cima do título; opcional)</small></label>
        <input id="etiqueta" name="etiqueta" maxlength="60" value="{{ old('etiqueta', $slide->etiqueta) }}" class="admin-campo" placeholder="Ex.: Comunicado">

        <label class="admin-rotulo" for="texto">Texto <small>(uma ou duas frases; opcional)</small></label>
        <textarea id="texto" name="texto" rows="3" maxlength="400" class="admin-campo">{{ old('texto', $slide->texto) }}</textarea>

        <div class="admin-caixa" style="margin-top:1.25rem">
            <h2>Botão</h2>
            <label class="admin-rotulo" for="botao_url" style="margin-top:0">Ligação <small>(deixe vazio para o slide não ter botão)</small></label>
            <input id="botao_url" name="botao_url" maxlength="500" value="{{ old('botao_url', $slide->botao_url) }}" class="admin-campo" placeholder="/documentos ou https://...">
            <label class="admin-rotulo" for="botao_texto">Texto do botão</label>
            <input id="botao_texto" name="botao_texto" maxlength="60" value="{{ old('botao_texto', $slide->botao_texto) }}" class="admin-campo" placeholder="Saber mais">
        </div>
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activo" value="1" @checked(old('activo', $slide->activo))> Slide activo <small>(aparece no carrossel)</small></label>
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $slide->exists ? 'Actualizar' : 'Guardar' }}</button>
                <a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver página inicial</a>
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Imagem de fundo</h2>
            <img @if ($slide->imagem) src="{{ $slide->imagem }}" @endif alt="" class="admin-destaque" data-destaque-pre @if (! $slide->imagem) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($slide->imagem) hidden @endif>Sem imagem: o slide fica com fundo preto. Use imagens largas (por exemplo 1600&times;700).</p>
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
