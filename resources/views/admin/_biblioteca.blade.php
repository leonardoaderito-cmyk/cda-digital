{{-- Autor: Leonardo (leonardoaderito-cmyk) - janela para escolher uma imagem da biblioteca. Espera $imagens. --}}
<dialog id="biblioteca" class="admin-dialogo">
    <div class="admin-dialogo-topo">
        <strong>Escolher imagem da biblioteca</strong>
        <button type="button" data-fechar-biblioteca aria-label="Fechar">&times;</button>
    </div>
    @if ($imagens->isEmpty())
        <p class="admin-nota">A biblioteca está vazia. Carregue imagens em <a href="{{ route('admin.imagens') }}" target="_blank" rel="noopener">Imagens</a> e volte a abrir esta página.</p>
    @else
        <div class="admin-media admin-media-escolha">
            @foreach ($imagens as $img)
                <button type="button" data-imagem-id="{{ $img->id }}" data-imagem-url="{{ $img->url() }}" data-imagem-alt="{{ $img->alt ?: $img->nome }}" title="{{ $img->nome }}">
                    <img src="{{ $img->url() }}" alt="{{ $img->alt ?: $img->nome }}" loading="lazy">
                </button>
            @endforeach
        </div>
    @endif
</dialog>
