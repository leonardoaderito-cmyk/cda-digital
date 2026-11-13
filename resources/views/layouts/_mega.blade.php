{{-- Autor: Leonardo (leonardoaderito-cmyk) - mega menu --}}
@php
    // Cada grupo: rotas que o marcam como activo, resumo, ligacao principal, colunas e um atalho em destaque
    $grupos = [
        'A CDA' => [
            'rotas' => ['instituicao', 'historia', 'orgaos', 'estatutos', 'parceiros'],
            'resumo' => 'Conheça a instituição que representa os despachantes aduaneiros de Moçambique.',
            'principal' => ['Quem somos', route('instituicao')],
            'colunas' => [
                'Instituição' => [['Quem somos', route('instituicao')], ['História', route('historia')], ['Órgãos sociais', route('orgaos')]],
                'Enquadramento' => [['Estatutos', route('estatutos')], ['Parceiros e ligações', route('parceiros')]],
            ],
            'atalho' => ['Fale com a CDA', 'Sede em Maputo e delegações em Nampula e na Beira.', 'Contactos', route('contactos')],
        ],
        'Documentação' => [
            'rotas' => ['documentos', 'documento.*', 'revista', 'revista.*'],
            'resumo' => 'Legislação, circulares e formulários oficiais num só lugar.',
            'principal' => ['Todos os documentos', route('documentos')],
            'colunas' => [
                'Legislação' => [['Leis', route('documentos', ['tipo' => 'Lei'])], ['Decretos', route('documentos', ['tipo' => 'Decreto'])], ['Regulamentos', route('documentos', ['tipo' => 'Regulamento'])], ['Estatutos', route('documentos', ['tipo' => 'Estatuto'])]],
                'Comunicação oficial' => [['Circulares', route('documentos', ['tipo' => 'Circular'])], ['Comunicados', route('documentos', ['tipo' => 'Comunicado'])], ['Convocatórias', route('documentos', ['tipo' => 'Convocatória'])], ['Ordens de serviço', route('documentos', ['tipo' => 'Ordem de Serviço'])]],
                'Publicações' => [['Relatórios', route('documentos', ['tipo' => 'Relatório'])], ['Boletins', route('documentos', ['tipo' => 'Boletim'])], ['Revista O Despachante', route('revista')]],
            ],
            'pesquisa' => true,
        ],
        'Notícias' => [
            'rotas' => ['noticias', 'noticia', 'actividades'],
            'resumo' => 'Acompanhe a vida da Câmara e do sector aduaneiro.',
            'principal' => ['Todas as notícias', route('noticias')],
            'colunas' => [
                'Actualidade' => [['Últimas notícias', route('noticias')], ['Actividades', route('actividades')]],
                'Agenda' => [['Eventos', route('eventos')]],
            ],
            'atalho' => ['Newsletter CDA', 'Receba notícias, circulares e comunicados no seu email.', 'Subscrever', route('inicio').'#newsletter-email'],
        ],
        'Membros' => [
            'rotas' => ['directorio', 'login', 'registo', 'membro.*'],
            'resumo' => 'Área reservada aos despachantes e directório público.',
            'principal' => ['Área do membro', route('membro.area')],
            // Com sessao: a conta do membro; sem sessao: como entrar. Os servicos aparecem sempre.
            'colunas' => auth()->check()
                ? [
                    'A minha conta' => [['Resumo', route('membro.area')], ['Os meus dados', route('membro.dados')], ['Segurança', route('membro.seguranca')]],
                    'Serviços' => [['Quotas e pagamentos', route('membro.quotas')], ['Requerimentos', route('membro.requerimentos')], ['Documentos', route('membro.documentos')]],
                ]
                : [
                    'Acesso' => [['Entrar', route('login')], ['Criar conta', route('registo')], ['Recuperar a senha', route('password.request')]],
                    'Serviços' => [['Quotas e pagamentos', route('membro.quotas')], ['Requerimentos', route('membro.requerimentos')], ['Documentos', route('membro.documentos')]],
                ],
            'atalho' => auth()->check()
                ? ['Cartão de membro', 'Mostre o seu cartão digital com código de verificação.', 'Ver o cartão', route('membro.cartao')]
                : ['Procura um despachante?', 'Consulte o directório público dos despachantes registados.', 'Abrir o directório', route('directorio')],
        ],
    ];

    // Paginas criadas na administracao e marcadas para aparecer no menu
    $extra = \App\Models\Pagina::where('tipo', 'conteudo')->where('activa', true)->where('no_menu', true)->orderBy('titulo')->get();
    if ($extra->isNotEmpty()) {
        $grupos['A CDA']['colunas']['Mais páginas'] = $extra->map(fn ($p) => [$p->titulo, $p->url()])->all();
    }

    // As ligacoes para paginas desactivadas na administracao saem do menu
    $activa = fn (string $url) => \App\Models\Pagina::urlActiva($url);
    foreach ($grupos as $titulo => $g) {
        $g['colunas'] = array_filter(array_map(fn ($ligacoes) => array_values(array_filter($ligacoes, fn ($l) => $activa($l[1]))), $g['colunas']));
        if (! $g['colunas']) {
            unset($grupos[$titulo]);
            continue;
        }
        if (! $activa($g['principal'][1])) {
            $g['principal'] = reset($g['colunas'])[0];
        }
        if (isset($g['atalho']) && ! $activa($g['atalho'][3])) {
            $g['atalho'] = null;
        }
        $grupos[$titulo] = $g;
    }
@endphp

<nav class="mega" aria-label="Principal">
    <button type="button" class="mega-toggle" data-mega-toggle aria-expanded="false" aria-controls="mega-lista">
        <span class="mega-toggle-icone" aria-hidden="true"></span> Menu
    </button>
    <ul id="mega-lista" class="mega-lista">
        <li class="mega-item"><a href="{{ route('inicio') }}" class="mega-botao {{ request()->routeIs('inicio') ? 'activo' : '' }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Página inicial</a></li>
        @foreach ($grupos as $titulo => $g)
            @php $id = 'mega-painel-'.$loop->index; $activo = request()->routeIs(...$g['rotas']); @endphp
            <li class="mega-item" data-mega-item>
                <button type="button" class="mega-botao {{ $activo ? 'activo' : '' }}" aria-expanded="false" aria-controls="{{ $id }}">{{ $titulo }} <span class="mega-seta" aria-hidden="true"></span></button>
                <div class="mega-painel" id="{{ $id }}">
                    <div class="mega-conteudo">
                        <div class="mega-intro">
                            <strong>{{ $titulo }}</strong>
                            <p>{{ $g['resumo'] }}</p>
                            <a href="{{ $g['principal'][1] }}" class="mega-intro-botao">{{ $g['principal'][0] }} &rarr;</a>
                        </div>
                        <div class="mega-colunas">
                            @foreach ($g['colunas'] as $cabecalho => $ligacoes)
                                <div class="mega-coluna">
                                    <h3>{{ $cabecalho }}</h3>
                                    <ul>
                                        @foreach ($ligacoes as [$rotulo, $url])
                                            <li><a href="{{ $url }}">{{ $rotulo }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        @if (! empty($g['pesquisa']) || ! empty($g['atalho']))
                        <div class="mega-atalho">
                            @if (! empty($g['pesquisa']))
                                <h3>Pesquisar documentos</h3>
                                <form method="GET" action="{{ route('documentos') }}" role="search">
                                    <label class="sr-only" for="mega-pesquisa">Título do documento</label>
                                    <input id="mega-pesquisa" type="search" name="q" placeholder="Título do documento">
                                    <button>Procurar</button>
                                </form>
                            @else
                                <h3>{{ $g['atalho'][0] }}</h3>
                                <p>{{ $g['atalho'][1] }}</p>
                                <a href="{{ $g['atalho'][3] }}" class="mega-atalho-botao">{{ $g['atalho'][2] }}</a>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
        @if (\App\Models\Pagina::estaActiva('eventos'))
            <li class="mega-item"><a href="{{ route('eventos') }}" class="mega-botao {{ request()->routeIs('eventos', 'evento') ? 'activo' : '' }}">Eventos</a></li>
        @endif
        @if (\App\Models\Pagina::estaActiva('contactos'))
            <li class="mega-item"><a href="{{ route('contactos') }}" class="mega-botao {{ request()->routeIs('contactos') ? 'activo' : '' }}">Contactos</a></li>
        @endif
    </ul>
</nav>
