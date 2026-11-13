@extends('layouts.admin')
@section('titulo', $publicidade->exists ? 'Editar anúncio' : 'Novo anúncio')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>{{ $publicidade->exists ? 'Editar anúncio' : 'Adicionar anúncio' }}</h1>
    <a href="{{ route('admin.publicidades') }}" class="admin-ligacao">&larr; Publicidade</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $publicidade->exists ? route('admin.publicidades.actualizar', $publicidade) : route('admin.publicidades.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="250" value="{{ old('titulo', $publicidade->titulo) }}" placeholder="Título do anúncio" class="admin-campo-titulo">

        <label class="admin-rotulo" for="anunciante">Anunciante <small>(empresa ou entidade; opcional)</small></label>
        <input id="anunciante" name="anunciante" maxlength="250" value="{{ old('anunciante', $publicidade->anunciante) }}" class="admin-campo">

        <label class="admin-rotulo" for="url">Ligação <small>(opcional; abre num separador novo)</small></label>
        <input id="url" name="url" type="url" maxlength="500" value="{{ old('url', $publicidade->url) }}" class="admin-campo" placeholder="https://...">

        <label class="admin-rotulo" for="inicio">Período de exibição <small>(deixe vazio para mostrar sempre)</small></label>
        <div class="admin-atalhos">
            <input id="inicio" name="inicio" type="date" value="{{ old('inicio', $publicidade->inicio?->format('Y-m-d')) }}" class="admin-campo" style="max-width:12rem" aria-label="Início">
            <span>até</span>
            <input id="fim" name="fim" type="date" value="{{ old('fim', $publicidade->fim?->format('Y-m-d')) }}" class="admin-campo" style="max-width:12rem" aria-label="Fim">
        </div>
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activo" value="1" @checked(old('activo', $publicidade->activo))> Visível no site</label>
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $publicidade->exists ? 'Actualizar' : 'Guardar' }}</button>
                <a href="{{ route('inicio') }}#videos" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Imagem do anúncio</h2>
            <img @if ($publicidade->imagem) src="{{ $publicidade->imagem }}" @endif alt="" class="admin-destaque" data-destaque-pre @if (! $publicidade->imagem) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($publicidade->imagem) hidden @endif>Formato recomendado: 800 × 600 px (4:3), JPG ou PNG.</p>
            <input type="hidden" name="imagem_biblioteca" data-destaque-id>
            <label class="admin-rotulo" for="imagem_ficheiro">Carregar do computador</label>
            <input id="imagem_ficheiro" type="file" name="imagem_ficheiro" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="admin-atalhos">
                <button type="button" class="admin-botao admin-botao-secundario" data-abrir-biblioteca>Escolher da biblioteca</button>
            </div>
        </section>
    </aside>
</form>

@include('admin._biblioteca')
@endsection
