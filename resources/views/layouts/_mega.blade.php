{{-- Autor: Leonardo (leonardoaderito-cmyk) - mega menu --}}
@php
    $grupos = [
        'A CDA' => [
            'resumo' => 'Conheça a instituição que representa os despachantes aduaneiros de Moçambique.',
            'colunas' => [
                'Instituição' => [['Quem somos', route('instituicao')], ['História', route('historia')], ['Órgãos sociais', route('orgaos')]],
                'Enquadramento' => [['Estatutos', route('estatutos')], ['Parceiros', route('parceiros')]],
            ],
        ],
        'Documentação' => [
            'resumo' => 'Legislação, circulares e formulários oficiais num só lugar.',
            'colunas' => [
                'Legislação' => [['Leis', route('documentos', ['tipo' => 'Lei'])], ['Decretos', route('documentos', ['tipo' => 'Decreto'])], ['Regulamentos', route('documentos', ['tipo' => 'Regulamento'])], ['Estatutos', route('documentos', ['tipo' => 'Estatuto'])]],
                'Comunicação oficial' => [['Circulares', route('documentos', ['tipo' => 'Circular'])], ['Comunicados', route('documentos', ['tipo' => 'Comunicado'])], ['Convocatórias', route('documentos', ['tipo' => 'Convocatória'])], ['Ordens de serviço', route('documentos', ['tipo' => 'Ordem de Serviço'])]],
                'Publicações' => [['Relatórios', route('documentos', ['tipo' => 'Relatório'])], ['Boletins', route('documentos', ['tipo' => 'Boletim'])], ['Todos os documentos', route('documentos')]],
            ],
        ],
        'Notícias' => [
            'resumo' => 'Acompanhe a vida da Câmara e do sector aduaneiro.',
            'colunas' => [
                'Actualidade' => [['Últimas notícias', route('noticias')], ['Actividades e agenda', route('actividades')]],
            ],
        ],
        'Membros' => [
            'resumo' => 'Directório público e área reservada aos despachantes.',
            'colunas' => [
                'Directório' => [['Procurar despachante', route('directorio')]],
                'Área reservada' => [['Entrar', route('login')], ['Criar conta', route('registo')], ['Quotas e requerimentos', route('membro.area')]],
            ],
        ],
    ];
@endphp

<nav class="mega" aria-label="Principal">
    <button type="button" class="mega-toggle" data-mega-toggle aria-expanded="false" aria-controls="mega-lista">
        <span class="sr-only">Menu</span>&#9776;
    </button>
    <ul id="mega-lista" class="mega-lista">
        <li class="mega-item"><a href="{{ route('inicio') }}" class="mega-botao {{ request()->routeIs('inicio') ? 'activo' : '' }}">Página inicial</a></li>
        @foreach ($grupos as $titulo => $g)
            <li class="mega-item" data-mega-item>
                <button type="button" class="mega-botao" aria-expanded="false" aria-haspopup="true">{{ $titulo }} <span aria-hidden="true">&#9662;</span></button>
                <div class="mega-painel">
                    <div class="mega-conteudo">
                        <div class="mega-intro">
                            <strong>{{ $titulo }}</strong>
                            <p>{{ $g['resumo'] }}</p>
                        </div>
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
                </div>
            </li>
        @endforeach
        <li class="mega-item"><a href="{{ route('eventos') }}" class="mega-botao">Eventos</a></li>
        <li class="mega-item"><a href="{{ route('contactos') }}" class="mega-botao">Contactos</a></li>
    </ul>
</nav>
