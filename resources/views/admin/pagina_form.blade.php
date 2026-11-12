@extends('layouts.admin')
@section('titulo', $pagina->exists ? 'Editar página' : 'Nova página')
@section('conteudo')
@php $fixa = $pagina->exists && \Illuminate\Support\Facades\Route::has($pagina->slug); @endphp
<div class="admin-cabecalho">
    <h1>{{ $pagina->exists ? 'Editar página' : 'Adicionar nova página' }}</h1>
    <a href="{{ route('admin.paginas') }}" class="admin-ligacao">&larr; Todas as páginas</a>
</div>

<form method="POST" action="{{ $pagina->exists ? route('admin.paginas.actualizar', $pagina) : route('admin.paginas.guardar') }}" class="admin-editor" data-editor-form>
    @csrf
    <div class="admin-editor-principal">
        <label class="sr-only" for="titulo">Título</label>
        <input id="titulo" name="titulo" required maxlength="200" value="{{ old('titulo', $pagina->titulo) }}" placeholder="Título da página" class="admin-campo-titulo">

        @if ($pagina->eSistema())
            <div class="admin-caixa">
                <h2>Secção do portal</h2>
                <p>Esta é uma secção do portal. O conteúdo é gerido no respectivo menu da administração; aqui pode mudar o título e activar ou desactivar a secção.</p>
            </div>
        @else
            <label class="admin-rotulo" for="resumo">Resumo <small>(texto de introdução por baixo do título)</small></label>
            <textarea id="resumo" name="resumo" rows="2" maxlength="500" class="admin-campo">{{ old('resumo', $pagina->resumo) }}</textarea>

            <div class="admin-rotulo">Conteúdo</div>
            @include('admin._editor', ['campo' => 'conteudo', 'html' => \App\Models\Pagina::limparHtml(old('conteudo', $pagina->conteudo))])
        @endif
    </div>

    <aside class="admin-editor-lado">
        <section class="admin-caixa">
            <h2>Publicação</h2>
            <label class="admin-opcao"><input type="checkbox" name="activa" value="1" @checked(old('activa', $pagina->activa))> Página activa <small>(visível para os visitantes)</small></label>
            @if (! $pagina->eSistema() && ! $fixa)
                <label class="admin-opcao"><input type="checkbox" name="no_menu" value="1" @checked(old('no_menu', $pagina->no_menu))> Mostrar no menu <small>(em «A CDA»)</small></label>
            @endif
            @if ($pagina->exists)
                <p class="admin-nota">Endereço: <code>{{ parse_url($pagina->url(), PHP_URL_PATH) }}</code><br>Alterada em {{ $pagina->updated_at?->format('d/m/Y H:i') }}</p>
            @else
                <p class="admin-nota">O endereço é criado a partir do título quando guardar.</p>
            @endif
            <div class="admin-atalhos">
                <button class="admin-botao">{{ $pagina->exists ? 'Actualizar' : 'Publicar' }}</button>
                @if ($pagina->exists && $pagina->activa)<a href="{{ $pagina->url() }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Ver página</a>@endif
            </div>
        </section>
    </aside>
</form>

@unless ($pagina->eSistema())
    @include('admin._biblioteca')
@endunless
@endsection
