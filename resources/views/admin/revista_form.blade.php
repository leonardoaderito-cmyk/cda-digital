@extends('layouts.admin')
@section('titulo', $revista->exists ? 'Editar edição' : 'Nova edição')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>{{ $revista->exists ? 'Editar edição da revista' : 'Nova edição da revista' }}</h1>
    <a href="{{ route('admin.revistas') }}" class="admin-ligacao">&larr; Revista</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $revista->exists ? route('admin.revistas.actualizar', $revista) : route('admin.revistas.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="200" value="{{ old('titulo', $revista->titulo) }}" placeholder="Título da revista" class="admin-campo-titulo">

        <label class="admin-rotulo" for="edicao">Edição <small>(ex.: Edição Julho 2026)</small></label>
        <input id="edicao" name="edicao" maxlength="120" value="{{ old('edicao', $revista->edicao) }}" class="admin-campo">

        <label class="admin-rotulo" for="data_publicacao">Data de publicação</label>
        <input id="data_publicacao" type="date" name="data_publicacao" value="{{ old('data_publicacao', $revista->data_publicacao?->toDateString()) }}" class="admin-campo" style="max-width:14rem">

        <label class="admin-rotulo" for="descricao">Descrição <small>(o que traz esta edição; opcional)</small></label>
        <textarea id="descricao" name="descricao" rows="5" maxlength="2000" class="admin-campo">{{ old('descricao', $revista->descricao) }}</textarea>

        <div class="admin-caixa" style="margin-top:1.25rem">
            <h2>Ficheiro PDF</h2>
            @if ($revista->exists)
                <p class="admin-nota">PDF actual: <a href="{{ route('revista.abrir', $revista) }}" target="_blank" rel="noopener">abrir</a>. Escolha outro ficheiro só se quiser substituí-lo.</p>
            @endif
            <label class="admin-rotulo" for="pdf" style="margin-top:0">PDF da revista <small>(até 80 MB)</small></label>
            <input id="pdf" type="file" name="pdf" accept="application/pdf" @required(! $revista->exists)>
        </div>
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activa" value="1" @checked(old('activa', $revista->activa))> Visível no site</label>
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $revista->exists ? 'Actualizar' : 'Publicar' }}</button>
                <a href="{{ route('revista') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Capa</h2>
            <img @if ($revista->capa) src="{{ $revista->capa }}" @endif alt="" class="admin-destaque admin-capa" data-destaque-pre @if (! $revista->capa) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($revista->capa) hidden @endif>Sem capa: o site mostra uma capa simples com o título. Use a imagem da primeira página (vertical, por exemplo 800&times;1100).</p>
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

@include('admin._biblioteca')
@endsection
