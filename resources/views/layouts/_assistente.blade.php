{{-- Autor: Leonardo (leonardoaderito-cmyk) - janela do Assistente CDA (pesquisa de documentos e respostas com o conteudo do site) --}}
<div class="assistente" data-assistente data-url="{{ route('assistente.perguntar') }}" data-token="{{ csrf_token() }}">
    {{-- So o logotipo da CDA; o nome fica para leitores de ecra e no titulo ao passar o rato --}}
    <button type="button" class="assistente-botao" data-assistente-abrir aria-expanded="false" aria-controls="assistente-janela" title="Assistente CDA">
        <img src="{{ asset('img/cda-logo.jpg') }}" alt="">
        <span class="sr-only">Assistente CDA</span>
    </button>

    <section id="assistente-janela" class="assistente-janela" role="dialog" aria-modal="false" aria-labelledby="assistente-titulo" hidden>
        <header class="assistente-topo">
            <span class="assistente-avatar" aria-hidden="true"><img src="{{ asset('img/cda-logo.jpg') }}" alt=""></span>
            <div class="assistente-topo-texto">
                <h2 id="assistente-titulo">Assistente CDA</h2>
                <p>Documentos, circulares e informação do site</p>
            </div>
            <button type="button" class="assistente-icone-botao" data-assistente-limpar title="Nova conversa"><x-icone nome="refresh" /><span class="sr-only">Nova conversa</span></button>
            <button type="button" class="assistente-icone-botao" data-assistente-fechar title="Fechar"><x-icone nome="close" /><span class="sr-only">Fechar</span></button>
        </header>

        <div class="assistente-mensagens" data-assistente-mensagens aria-live="polite">
            <div class="assistente-mensagem assistente-mensagem-bot">
                <p>Olá! Sou o assistente da CDA. Posso procurar documentos (circulares, ordens de serviço, boletins) e responder com a informação publicada neste site.</p>
            </div>
            <div class="assistente-sugestoes" data-assistente-sugestoes>
                @foreach (['Circulares de 2023', 'Como ser despachante aduaneiro?', 'Estatuto da CDA', 'Contactos e horário', 'Último boletim'] as $sugestao)
                    <button type="button" data-assistente-sugestao>{{ $sugestao }}</button>
                @endforeach
            </div>
        </div>

        <form class="assistente-formulario" data-assistente-formulario>
            <label for="assistente-pergunta" class="sr-only">A sua pergunta</label>
            <textarea id="assistente-pergunta" rows="1" maxlength="500" placeholder="Escreva a sua pergunta…" data-assistente-pergunta required></textarea>
            <button type="submit" class="assistente-enviar" data-assistente-enviar><x-icone nome="send" /><span class="sr-only">Enviar</span></button>
        </form>
        <p class="assistente-nota">Respostas baseadas no conteúdo publicado no site. Confirme sempre no documento original.</p>
    </section>

    {{-- Icones das fontes, copiados pelo JavaScript --}}
    <template data-assistente-icone="documento"><x-icone nome="picture_as_pdf" /></template>
    <template data-assistente-icone="lista"><x-icone nome="folder_open" /></template>
    <template data-assistente-icone="pagina"><x-icone nome="description" /></template>
    <template data-assistente-icone="noticia"><x-icone nome="newspaper" /></template>
    <template data-assistente-icone="evento"><x-icone nome="event" /></template>
</div>
