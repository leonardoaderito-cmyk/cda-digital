@extends('layouts.app')
@section('conteudo')
@use('App\Models\Pagina')
@use('Illuminate\Support\Str')
@php
    $noticiasActivas = Pagina::estaActiva('noticias');
    $documentosActivos = Pagina::estaActiva('documentos');
    $eventosActivos = Pagina::estaActiva('eventos');
    $imagemDe = fn ($caminho) => Str::startsWith($caminho, ['http://', 'https://', '/']) ? $caminho : asset('storage/'.$caminho);
    // Carrossel: slides definidos em Administracao > Carrossel; sem nenhum activo, mostra as ultimas noticias
    $destaques = $slides->map(fn ($s) => [
        'etiqueta' => $s->etiqueta, 'titulo' => $s->titulo, 'texto' => $s->texto, 'imagem' => $s->imagem,
        'botao' => $s->botao_url ? ($s->botao_texto ?: 'Saber mais') : null, 'url' => $s->botao_url,
    ]);
    if ($destaques->isEmpty() && $noticiasActivas) {
        $destaques = $noticias->take(4)->map(fn ($n) => [
            'etiqueta' => $n->categoria ?: 'Notícia', 'titulo' => $n->titulo,
            'texto' => trim($n->data?->format('d/m/Y').' · '.Str::limit($n->resumo, 150), ' ·'),
            'imagem' => $n->imagem ? $imagemDe($n->imagem) : null, 'botao' => 'Ler mais', 'url' => route('noticia', $n->slug),
        ]);
    }
    $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
@endphp

{{-- Autor: Leonardo (leonardoaderito-cmyk) - pagina inicial: destaques e noticias com barra lateral, depois as seccoes do portal --}}
<div class="inicio-grelha">
    <div>
        @if ($destaques->isNotEmpty())
        <section data-carrossel class="carrossel mb-8" aria-label="Destaques">
            @foreach ($destaques as $d)
                <article data-slide class="slide {{ $loop->first ? 'slide-activo' : '' }} {{ $d['imagem'] ? '' : 'slide-sem-imagem' }}">
                    @if ($d['imagem'])<img src="{{ $d['imagem'] }}" alt="" class="slide-imagem">@endif
                    <div class="slide-texto">
                        @if ($d['etiqueta'])<span class="slide-etiqueta">{{ $d['etiqueta'] }}</span>@endif
                        <h1>{{ $d['titulo'] }}</h1>
                        @if ($d['texto'])<p>{{ $d['texto'] }}</p>@endif
                        @if ($d['botao'])<a href="{{ $d['url'] }}" class="slide-ligacao">{{ $d['botao'] }}</a>@endif
                    </div>
                </article>
            @endforeach
            @if ($destaques->count() > 1)
                <div class="carrossel-controlos">
                    <button data-anterior aria-label="Anterior" class="seta">&lsaquo;</button>
                    @foreach ($destaques as $i => $d)<button data-ponto aria-label="Destaque {{ $i + 1 }}" class="ponto"></button>@endforeach
                    <button data-seguinte aria-label="Seguinte" class="seta">&rsaquo;</button>
                </div>
            @endif
        </section>
        @endif

        @if ($noticiasActivas)
        <section class="mb-8">
            <div class="titulo-seccao">
                <h2><small>Actualidade</small>Flash de Notícias</h2>
                <a href="{{ route('noticias') }}">Todas as notícias <x-icone nome="chevron_right" /></a>
            </div>
            <div class="flash-cartoes">
                @forelse ($noticias as $n)
                    <article class="flash-cartao">
                        <a href="{{ route('noticia', $n->slug) }}" class="flash-media" aria-label="Ler: {{ $n->titulo }}">
                            @if ($n->imagem)
                                <img src="{{ $imagemDe($n->imagem) }}" alt="" loading="lazy">
                            @else
                                <span class="flash-sem-imagem" aria-hidden="true"><strong>CDA</strong><small>Notícia</small></span>
                            @endif
                            <time class="flash-data" datetime="{{ $n->data?->toDateString() }}">
                                <strong>{{ $n->data?->format('d') ?? '--' }}</strong>
                                <span>{{ $n->data ? $meses[$n->data->month].' '.$n->data->format('y') : 'CDA' }}</span>
                            </time>
                        </a>
                        <div class="flash-conteudo">
                            <div class="flash-meta">
                                <span>{{ $n->categoria ?: 'Actualidade' }}</span>
                                @if ($n->data)<time datetime="{{ $n->data->toDateString() }}">{{ $n->data->format('d/m/Y') }}</time>@endif
                            </div>
                            <h3><a href="{{ route('noticia', $n->slug) }}">{{ $n->titulo }}</a></h3>
                            @if ($n->resumo)<p>{{ Str::limit($n->resumo, 125) }}</p>@endif
                            <a href="{{ route('noticia', $n->slug) }}" class="flash-ler" aria-label="Ler a notícia {{ $n->titulo }}">Ler notícia <span aria-hidden="true">&rarr;</span></a>
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-cda-cinza">Ainda não há notícias publicadas.</p>
                @endforelse
            </div>
        </section>
        @endif
    </div>

    <aside class="inicio-lateral">
        @if ($documentosActivos && $tiposDocumento->isNotEmpty())
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2><small>Centro Documental</small>Documentos</h2>
                <a href="{{ route('documentos') }}">Ver todos</a>
            </div>
            <ul class="lateral-lista">
                @foreach ($tiposDocumento as $tipo => $total)
                    <li><a href="{{ route('documentos', ['tipo' => $tipo]) }}"><span>{{ $tipo }}</span><span class="lateral-conta">{{ $total }}</span></a></li>
                @endforeach
            </ul>
        </section>
        @endif

        @if ($revista && Pagina::estaActiva('revista'))
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2><small>Publicação oficial</small>Revista</h2>
                <a href="{{ route('revista') }}">Edições</a>
            </div>
            {{-- Cartao da ultima edicao: capa pequena + titulo, data e botao para o PDF --}}
            <div class="lateral-revista">
                <a href="{{ route('revista.abrir', $revista) }}" target="_blank" rel="noopener" class="lateral-revista-capa" aria-label="Abrir a revista {{ $revista->nome() }} (PDF)">
                    @if ($revista->capa)
                        <img src="{{ $revista->capa }}" alt="">
                    @else
                        <span class="revista-sem-capa"><strong>{{ $revista->titulo }}</strong></span>
                    @endif
                </a>
                <div class="lateral-revista-texto">
                    <small>Edição mais recente</small>
                    <strong>{{ $revista->titulo }}</strong>
                    <span>{{ collect([$revista->edicao, $revista->data_publicacao?->format('d/m/Y')])->filter()->implode(' · ') }}</span>
                    <a href="{{ route('revista.abrir', $revista) }}" target="_blank" rel="noopener" class="lateral-revista-botao"><x-icone nome="picture_as_pdf" /> Abrir PDF</a>
                </div>
            </div>
        </section>
        @endif

        @if ($documentosActivos && $publicacoes->isNotEmpty())
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2><small>Centro Documental</small>Publicações recentes</h2>
                <a href="{{ route('documentos') }}">Ver todas</a>
            </div>
            <ul class="lateral-publicacoes">
                @foreach ($publicacoes as $d)
                    <li>
                        <a href="{{ route('documento.abrir', $d) }}" target="_blank" rel="noopener" title="{{ $d->titulo }}">
                            <x-icone nome="picture_as_pdf" />
                            <span>
                                <strong>{{ $d->titulo }}</strong>
                                <small>{{ collect([$d->tipo, $d->ano])->filter()->implode(' · ') }}</small>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
        @endif

        @if ($eventosActivos)
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2><small>Agenda</small>Eventos</h2>
                <a href="{{ route('eventos') }}">Ver todos</a>
            </div>
            <ul class="lateral-agenda">
                @forelse ($eventos as $evento)
                    <li>
                        <a href="{{ route('evento', $evento->slug) }}">
                            <time datetime="{{ $evento->data_evento?->toDateString() }}">
                                <strong>{{ $evento->data_evento?->format('d') ?? '--' }}</strong>
                                <span>{{ $evento->data_evento ? $meses[$evento->data_evento->month] : 'Breve' }}</span>
                            </time>
                            <span><strong>{{ $evento->titulo }}</strong>@if ($evento->local)<small>{{ $evento->local }}</small>@endif</span>
                        </a>
                    </li>
                @empty
                    <li class="lateral-vazio">Consulte brevemente os próximos eventos da CDA.</li>
                @endforelse
            </ul>
        </section>
        @endif
    </aside>
</div>

@if ($emActividade->isNotEmpty())
<section class="inicio-seccao">
    <div class="titulo-seccao">
        <h2><small>CDA em actividade</small>Onde a classe se encontra</h2>
        <a href="{{ $eventosActivos ? route('eventos') : route('noticias') }}">Todas as actividades <x-icone nome="chevron_right" /></a>
    </div>
    <div class="actividade-cartoes">
        @foreach ($emActividade as $c)
            <a href="{{ $c['url'] }}" class="actividade-cartao">
                <span class="actividade-media">
                    @if ($c['imagem'])
                        <img src="{{ $imagemDe($c['imagem']) }}" alt="" loading="lazy">
                    @else
                        <span class="flash-sem-imagem" aria-hidden="true"><strong>CDA</strong><small>{{ $c['etiqueta'] }}</small></span>
                    @endif
                </span>
                <span class="actividade-texto">
                    <small class="actividade-etiqueta">{{ $c['etiqueta'] }}</small>
                    <strong>{{ $c['titulo'] }}</strong>
                    @if ($c['detalhe'])<small>{{ $c['detalhe'] }}</small>@endif
                </span>
            </a>
        @endforeach
    </div>
</section>
@endif

@if ($videos->isNotEmpty())
{{-- Videos dos eventos: o leitor toca sem som quando a seccao aparece e passa ao seguinte quando um termina --}}
<section class="inicio-seccao" id="videos" aria-label="Vídeos dos eventos da CDA">
    <div class="titulo-seccao">
        <h2><small>CDA em vídeo</small>Vídeos dos eventos</h2>
    </div>
    @php $primeiro = $videos->first(); @endphp
    <div class="videos" data-videos>
        <div class="videos-leitor" data-videos-leitor>
            {{-- Sem JavaScript fica a capa com ligacao para o video --}}
            <a href="{{ $primeiro->eYoutube() ? 'https://www.youtube.com/watch?v='.$primeiro->youtube_id : $primeiro->urlFicheiro() }}" target="_blank" rel="noopener" class="videos-capa" data-videos-capa>
                @if ($primeiro->imagemCapa())<img src="{{ $primeiro->imagemCapa() }}" alt="">@endif
                <span class="videos-play" aria-hidden="true"><x-icone nome="play_arrow" /></span>
                <span class="sr-only">Ver o vídeo {{ $primeiro->titulo }}</span>
            </a>
        </div>
        <div class="videos-info">
            <div class="videos-actual" data-videos-actual>
                <strong>{{ $primeiro->titulo }}</strong>
                @if ($primeiro->descricao || $primeiro->data)<span>{{ collect([$primeiro->data?->format('d/m/Y'), $primeiro->descricao])->filter()->implode(' · ') }}</span>@endif
            </div>
            @if ($videos->count() > 1)
            <ol class="videos-lista">
                @foreach ($videos as $v)
                    <li>
                        <button type="button" data-video="{{ $loop->index }}"
                            data-tipo="{{ $v->eYoutube() ? 'youtube' : 'ficheiro' }}"
                            data-src="{{ $v->eYoutube() ? $v->youtube_id : $v->urlFicheiro() }}"
                            data-titulo="{{ $v->titulo }}"
                            data-detalhe="{{ collect([$v->data?->format('d/m/Y'), $v->descricao])->filter()->implode(' · ') }}"
                            @if ($v->capa) data-capa="{{ $v->capa }}" @endif
                            @if ($loop->first) aria-current="true" @endif>
                            <span class="videos-miniatura">
                                @if ($v->imagemCapa())<img src="{{ $v->imagemCapa() }}" alt="" loading="lazy">@endif
                                <x-icone nome="play_arrow" />
                            </span>
                            <span class="videos-item-texto"><strong>{{ $v->titulo }}</strong>@if ($v->data)<small>{{ $v->data->format('d/m/Y') }}</small>@endif</span>
                        </button>
                    </li>
                @endforeach
            </ol>
            @else
                <button type="button" hidden data-video="0" data-tipo="{{ $primeiro->eYoutube() ? 'youtube' : 'ficheiro' }}" data-src="{{ $primeiro->eYoutube() ? $primeiro->youtube_id : $primeiro->urlFicheiro() }}" data-titulo="{{ $primeiro->titulo }}" data-detalhe="{{ collect([$primeiro->data?->format('d/m/Y'), $primeiro->descricao])->filter()->implode(' · ') }}"></button>
            @endif
            {{-- Publicidade: os anuncios rodam sozinhos; sem anuncios fica o convite para anunciar --}}
            <div class="publicidades" data-publicidades aria-label="Publicidade">
                <span class="publicidades-etiqueta">Publicidade</span>
                @forelse ($publicidades as $p)
                    <a @if ($p->url) href="{{ $p->url }}" target="_blank" rel="noopener sponsored" @endif class="publicidade @if ($loop->first) activa @endif" data-publicidade @unless ($loop->first) aria-hidden="true" tabindex="-1" @endunless>
                        <img src="{{ $p->imagem }}" alt="{{ $p->titulo }}" @unless ($loop->first) loading="lazy" @endunless>
                        <span class="publicidade-texto"><strong>{{ $p->titulo }}</strong>@if ($p->anunciante)<small>{{ $p->anunciante }}</small>@endif</span>
                    </a>
                @empty
                    <a href="{{ route('contactos') }}" class="publicidade publicidade-vazia activa">
                        <img src="{{ asset('img/cda-logo.jpg') }}" alt="" class="publicidade-vazia-logo">
                        <strong>Anuncie no portal da CDA</strong>
                        <small>Chegue aos despachantes aduaneiros e operadores do comércio externo de Moçambique.</small>
                        <span class="publicidade-vazia-accao">Fale connosco &rarr;</span>
                    </a>
                @endforelse
                @if ($publicidades->count() > 1)
                    <div class="publicidades-pontos">
                        @foreach ($publicidades as $p)
                            <button type="button" data-publicidade-ponto="{{ $loop->index }}" aria-label="Anúncio {{ $loop->iteration }}: {{ $p->titulo }}" @if ($loop->first) aria-current="true" @endif></button>
                        @endforeach
                    </div>
                @endif
            </div>
            <a href="https://www.youtube.com/@cdamo%C3%A7ambique" target="_blank" rel="noopener" class="videos-canal"><x-icone nome="smart_display" /> Mais vídeos no canal da CDA no YouTube <x-icone nome="open_in_new" /></a>
        </div>
    </div>
</section>
@endif

{{-- Acervo e servicos num so painel: marca da CDA a esquerda, cartoes a direita --}}
<section class="portal-cda" aria-label="Centro Documental e serviços da CDA">
    <div class="portal-cda-marca">
        <span class="portal-cda-logo"><img src="{{ asset('img/cda-logo.jpg') }}" alt="Logotipo da CDA"></span>
        <span class="portal-cda-sobre">CDA Digital</span>
        <h2>Câmara dos Despachantes Aduaneiros de Moçambique</h2>
        <p>O acervo documental e os serviços da Câmara, reunidos num só lugar.</p>
        @if ($documentosActivos)
            <a href="{{ route('documentos') }}" class="portal-cda-botao"><x-icone nome="search" /> Pesquisar no acervo</a>
        @endif
    </div>

    <div class="portal-cda-conteudo">
        @if ($documentosActivos)
        <div class="portal-cda-bloco">
            <h3><small>Centro Documental</small>O acervo da CDA</h3>
            <div class="acervo-cartoes">
                @foreach ($grupos as $chave => $g)
                    <a href="{{ route('documentos', ['grupo' => $chave]) }}" class="acervo-cartao">
                        <span class="acervo-topo">
                            <x-icone :nome="['legislacao' => 'gavel', 'circulares' => 'description', 'regulamentos' => 'library_books', 'publicacoes' => 'newspaper'][$chave] ?? 'folder_open'" />
                            <strong data-contador="{{ $g['total'] }}">{{ $g['total'] }}</strong>
                        </span>
                        <span class="acervo-nome">{{ $g['nome'] }}</span>
                        <small>{{ $g['descricao'] }}</small>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="portal-cda-bloco">
            <h3><small>Serviços</small>O que precisa, num clique</h3>
            <div class="servico-cartoes">
                @foreach ([
                    ['Adesão e fichas', 'Inscrição de novos membros e fichas de adesão.', 'registo', 'how_to_reg', null],
                    ['Directório de membros', 'Procure um despachante registado na CDA.', 'directorio', 'badge', 'directorio'],
                    ['Área do membro', 'Quotas, declarações e requerimentos.', 'membro.area', 'person', null],
                    ['Contactos', 'Fale com a Câmara ou visite a sede.', 'contactos', 'contact_support', 'contactos'],
                ] as [$titulo, $texto, $rota, $icone, $pagina])
                    @continue($pagina && ! Pagina::estaActiva($pagina))
                    <a href="{{ route($rota) }}" class="servico-cartao">
                        <span class="servico-icone"><x-icone :nome="$icone" /></span>
                        <span class="servico-texto"><strong>{{ $titulo }}</strong><span>{{ $texto }}</span></span>
                        <x-icone nome="chevron_right" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if ($parceiros->isNotEmpty() && Pagina::estaActiva('parceiros'))
<section class="inicio-seccao">
    <div class="titulo-seccao">
        <h2><small>Parceiros e instituições</small>O sector, em conjunto</h2>
        <a href="{{ route('parceiros') }}">Ver parceiros <x-icone nome="chevron_right" /></a>
    </div>
    {{-- Logotipos geridos em Administracao > Parceiros (so os que tem logotipo) --}}
    <div class="parceiros">
        @foreach ($parceiros as $p)
            @if ($p->url)
                <a href="{{ $p->url }}" target="_blank" rel="noopener" title="{{ $p->nome }}"><img src="{{ $p->logo }}" alt="{{ $p->nome }}" loading="lazy"></a>
            @else
                <span title="{{ $p->nome }}"><img src="{{ $p->logo }}" alt="{{ $p->nome }}" loading="lazy"></span>
            @endif
        @endforeach
    </div>
</section>
@endif
@endsection
