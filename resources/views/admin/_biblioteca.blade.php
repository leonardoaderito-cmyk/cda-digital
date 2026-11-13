{{-- Autor: Leonardo (leonardoaderito-cmyk) - janela para escolher uma ou varias imagens da biblioteca, ou carrega-las do computador. Espera $imagens. --}}
<dialog id="biblioteca" class="admin-dialogo" data-carregar-url="{{ route('admin.imagens.guardar') }}">
    <div class="admin-dialogo-topo">
        <strong data-biblioteca-titulo>Escolher imagem da biblioteca</strong>
        <button type="button" data-fechar-biblioteca aria-label="Fechar">&times;</button>
    </div>
    <div class="admin-biblioteca-carregar">
        <label class="admin-botao admin-botao-secundario">
            Carregar do computador
            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-biblioteca-ficheiros class="sr-only">
        </label>
        <span class="admin-nota" data-biblioteca-estado>JPG, PNG, WebP ou GIF, até 5 MB cada (máx. 20 de cada vez).</span>
    </div>
    <p class="admin-nota" data-biblioteca-vazia @unless ($imagens->isEmpty()) hidden @endunless>A biblioteca está vazia. Carregue imagens do computador.</p>
    <div class="admin-media admin-media-escolha" data-biblioteca-grelha>
        @foreach ($imagens as $img)
            <button type="button" data-imagem-id="{{ $img->id }}" data-imagem-url="{{ $img->url() }}" data-imagem-alt="{{ $img->alt ?: $img->nome }}" title="{{ $img->nome }}" aria-pressed="false">
                <img src="{{ $img->url() }}" alt="{{ $img->alt ?: $img->nome }}" loading="lazy">
            </button>
        @endforeach
    </div>
    <div class="admin-dialogo-rodape" data-biblioteca-rodape hidden>
        <label class="admin-opcao"><input type="checkbox" data-biblioteca-galeria checked> Mostrar lado a lado (galeria)</label>
        <button type="button" class="admin-botao" data-biblioteca-inserir disabled>Inserir imagens</button>
    </div>
</dialog>
