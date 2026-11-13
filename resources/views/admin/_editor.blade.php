{{-- Autor: Leonardo (leonardoaderito-cmyk) - editor rico comum (paginas, noticias). Espera $campo (nome do campo) e $html (conteudo ja limpo). --}}
<div class="editor" data-editor>
    <div class="editor-barra" role="toolbar" aria-label="Formatação">
        <label class="editor-select" title="Tipo de texto"><span class="sr-only">Tipo de texto</span><select data-formato>
            <option value="p">Parágrafo</option><option value="h2">Título</option><option value="h3">Subtítulo</option><option value="h4">Título pequeno</option><option value="blockquote">Citação</option>
        </select></label>
        <span class="editor-sep"></span>
        <button type="button" data-cmd="bold" title="Negrito"><b>N</b></button>
        <button type="button" data-cmd="italic" title="Itálico"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Sublinhado"><u>S</u></button>
        <button type="button" data-cmd="strikeThrough" title="Riscado"><s>R</s></button>
        <span class="editor-sep"></span>
        <button type="button" data-alinhar="esquerda" title="Alinhar à esquerda">&#8676;</button>
        <button type="button" data-alinhar="centro" title="Centrar texto">&#8596;</button>
        <button type="button" data-alinhar="direita" title="Alinhar à direita">&#8677;</button>
        <button type="button" data-alinhar="justificado" title="Justificar texto">&#9776;</button>
        <span class="editor-sep"></span>
        <button type="button" data-cor="vermelha" class="editor-cor editor-cor-vermelha" title="Texto vermelho">A</button>
        <button type="button" data-cor="preta" class="editor-cor editor-cor-preta" title="Texto preto">A</button>
        <button type="button" data-cor="cinza" class="editor-cor editor-cor-cinza" title="Texto cinzento">A</button>
        <button type="button" data-cor="destaque" class="editor-cor editor-cor-destaque" title="Realçar texto">A</button>
        <button type="button" data-limpar-formato title="Limpar formatação">Limpar</button>
        <span class="editor-sep"></span>
        <button type="button" data-cmd="insertUnorderedList" title="Lista">&bull; Lista</button>
        <button type="button" data-cmd="insertOrderedList" title="Lista numerada">1. Lista</button>
        <span class="editor-sep"></span>
        <button type="button" data-ligacao title="Inserir ligação">Ligação</button>
        <button type="button" data-cmd="unlink" title="Remover ligação">Sem ligação</button>
        <button type="button" data-abrir-biblioteca title="Inserir uma ou várias imagens">Imagens</button>
        <button type="button" data-video title="Incorporar vídeo do YouTube, Vimeo ou MP4">Vídeo</button>
        <button type="button" data-tabela="inserir" title="Inserir tabela">Tabela</button>
        <button type="button" data-tabela="linha" title="Adicionar linha à tabela">+ Linha</button>
        <button type="button" data-tabela="coluna" title="Adicionar coluna à tabela">+ Coluna</button>
        <button type="button" data-tabela="remover" title="Remover tabela">Remover tabela</button>
        <span class="editor-sep"></span>
        <button type="button" data-imagem-pos="esquerda" title="Imagem à esquerda">Img &#8676;</button>
        <button type="button" data-imagem-pos="centro" title="Imagem ao centro">Img &#8596;</button>
        <button type="button" data-imagem-pos="direita" title="Imagem à direita">Img &#8677;</button>
        <button type="button" data-imagem-pos="larga" title="Imagem a toda a largura">Img larga</button>
        <button type="button" data-imagem-mover="cima" title="Mover imagem para cima">Img &uarr;</button>
        <button type="button" data-imagem-mover="baixo" title="Mover imagem para baixo">Img &darr;</button>
        <button type="button" data-imagem-remover title="Remover imagem seleccionada">Remover img</button>
        <span class="editor-sep"></span>
        <button type="button" data-cmd="undo" title="Anular">&#8630;</button>
        <button type="button" data-cmd="redo" title="Refazer">&#8631;</button>
        <button type="button" data-html title="Ver e editar o código HTML" class="editor-fim">HTML</button>
    </div>
    <p class="editor-ajuda">Seleccione texto para formatar. Em «Imagens» pode escolher várias de uma vez ou carregá-las do computador. Clique numa imagem para a alinhar ou mover; duplo clique para a legenda. Nas tabelas, clique numa célula antes de acrescentar linhas ou colunas.</p>
    <div class="editor-area conteudo-texto" contenteditable="true" data-editor-area>{!! $html !!}</div>
    <textarea name="{{ $campo }}" class="editor-codigo" data-editor-codigo hidden spellcheck="false" aria-label="Código HTML"></textarea>
</div>
