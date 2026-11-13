@extends('layouts.admin')
@section('titulo', $parceiro->exists ? 'Editar parceiro' : 'Novo parceiro')
@section('conteudo')
<div class="admin-cabecalho">
    <h1>{{ $parceiro->exists ? 'Editar parceiro' : 'Adicionar parceiro' }}</h1>
    <a href="{{ route('admin.parceiros') }}" class="admin-ligacao">&larr; Parceiros</a>
</div>

<form method="POST" enctype="multipart/form-data" action="{{ $parceiro->exists ? route('admin.parceiros.actualizar', $parceiro) : route('admin.parceiros.guardar') }}" class="admin-editor">
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="nome">Nome</label>
        <input id="nome" name="nome" required maxlength="250" value="{{ old('nome', $parceiro->nome) }}" placeholder="Nome da instituição" class="admin-campo-titulo">

        <label class="admin-rotulo" for="sigla">Sigla <small>(aparece numa placa quando não há logotipo; opcional)</small></label>
        <input id="sigla" name="sigla" maxlength="30" value="{{ old('sigla', $parceiro->sigla) }}" class="admin-campo" style="max-width:14rem" placeholder="Ex.: AT">

        <label class="admin-rotulo" for="categoria">Categoria</label>
        <select id="categoria" name="categoria" class="admin-campo" style="max-width:24rem">
            @foreach (\App\Models\Parceiro::CATEGORIAS as $chave => $nome)
                <option value="{{ $chave }}" @selected(old('categoria', $parceiro->categoria) === $chave)>{{ $nome }}</option>
            @endforeach
        </select>

        <label class="admin-rotulo" for="descricao">Descrição <small>(uma frase sobre a relação com a CDA)</small></label>
        <textarea id="descricao" name="descricao" rows="3" maxlength="400" class="admin-campo">{{ old('descricao', $parceiro->descricao) }}</textarea>

        <label class="admin-rotulo" for="url">Sítio web <small>(opcional; abre num separador novo)</small></label>
        <input id="url" name="url" type="url" maxlength="500" value="{{ old('url', $parceiro->url) }}" class="admin-campo" placeholder="https://...">
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activo" value="1" @checked(old('activo', $parceiro->activo))> Visível no site</label>
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $parceiro->exists ? 'Actualizar' : 'Guardar' }}</button>
                <a href="{{ route('parceiros') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver no site</a>
            </div>
        </section>

        <section class="admin-caixa" data-destaque>
            <h2>Logotipo</h2>
            <img @if ($parceiro->logo) src="{{ $parceiro->logo }}" @endif alt="" class="admin-destaque admin-logo" data-destaque-pre @if (! $parceiro->logo) hidden @endif>
            <p class="admin-nota" data-destaque-vazio @if ($parceiro->logo) hidden @endif>Sem logotipo: o site mostra uma placa com a sigla. Prefira PNG ou SVG com fundo transparente ou branco.</p>
            <input type="hidden" name="imagem_biblioteca" data-destaque-id>
            <input type="hidden" name="remover_imagem" value="0" data-destaque-remover>
            <label class="admin-rotulo" for="imagem_ficheiro">Carregar do computador</label>
            <input id="imagem_ficheiro" type="file" name="imagem_ficheiro" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="admin-atalhos">
                <button type="button" class="admin-botao admin-botao-secundario" data-abrir-biblioteca>Escolher da biblioteca</button>
                <button type="button" class="admin-ligacao perigo" data-destaque-limpar>Remover logotipo</button>
            </div>
        </section>
    </aside>
</form>

@include('admin._biblioteca')
@endsection
