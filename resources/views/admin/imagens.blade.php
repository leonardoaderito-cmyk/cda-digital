@extends('layouts.admin')
@section('titulo', 'Imagens')
@section('conteudo')
<div class="admin-cabecalho">
    <div><h1>Biblioteca de imagens</h1><p>Imagens para as páginas, notícias, eventos e carrossel. Clique numa imagem para editar o nome, o texto alternativo ou copiar o endereço.</p></div>
</div>

<form method="POST" action="{{ route('admin.imagens.guardar') }}" enctype="multipart/form-data" class="admin-caixa admin-carregar" data-upload-imagens data-max-ficheiro="5242880" data-max-total="104857600">
    @csrf
    <label for="ficheiros"><strong>Adicionar imagens</strong><small>JPG, PNG, WebP ou GIF, até 5 MB cada. Pode escolher várias de uma vez.</small></label>
    <input id="ficheiros" type="file" name="ficheiros[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
    <button class="admin-botao">Carregar</button>
    <p class="admin-upload-erro" data-upload-erro role="alert" hidden></p>
</form>

@if ($imagens->isEmpty())
    <p class="admin-nota">Ainda não há imagens. As imagens carregadas aqui podem ser inseridas nas páginas e usadas como imagem de destaque das notícias.</p>
@else
    <div class="admin-media">
        @foreach ($imagens as $img)
            <details class="admin-media-item">
                <summary title="{{ $img->nome }}"><img src="{{ $img->url() }}" alt="{{ $img->alt ?: $img->nome }}" loading="lazy"><span>{{ $img->nome }}</span></summary>
                <div class="admin-media-detalhe">
                    <p class="admin-nota">{{ $img->largura }}&times;{{ $img->altura }} px &middot; {{ number_format($img->tamanho / 1024, 0, ',', '.') }} KB &middot; {{ $img->created_at?->format('d/m/Y') }}</p>
                    <label class="admin-rotulo">Endereço<input readonly value="{{ $img->url() }}" class="admin-campo" onfocus="this.select()"></label>
                    <form method="POST" action="{{ route('admin.imagens.actualizar', $img) }}">
                        @csrf
                        <label class="admin-rotulo">Nome<input name="nome" required maxlength="180" value="{{ $img->nome }}" class="admin-campo"></label>
                        <label class="admin-rotulo">Texto alternativo<input name="alt" maxlength="255" value="{{ $img->alt }}" class="admin-campo" placeholder="Descreve a imagem a quem não a vê"></label>
                        <button class="admin-botao admin-botao-secundario">Guardar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.imagens.apagar', $img) }}" onsubmit="return confirm('Remover esta imagem? As páginas que a usam ficam sem ela.')">@csrf @method('DELETE')<button class="admin-ligacao perigo">Remover definitivamente</button></form>
                </div>
            </details>
        @endforeach
    </div>
    <div class="mt-6">{{ $imagens->links() }}</div>
@endif
@endsection
