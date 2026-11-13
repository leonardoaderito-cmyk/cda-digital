<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Painel') &lsaquo; Administração CDA</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#000000">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Autor: Leonardo (leonardoaderito-cmyk) - area de administracao com barra superior e menu lateral --}}
@php
    $utilizador = auth()->user();
    // Contagens do que espera por alguem; aparecem como etiqueta ao lado da entrada do menu.
    $porTratar = [
        'admin.membros' => \App\Models\User::where('ativo', false)->where('is_admin', false)->count(),
        'admin.quotas' => \App\Models\Quota::where('talao_estado', 'em_validacao')->count(),
        'admin.requerimentos' => \App\Models\Requerimento::where('estado', 'em_analise')->count(),
        'admin.mensagens' => \App\Models\ContactoMensagem::where('lida', false)->count(),
    ];
    $dicas = [
        'admin.membros' => ['conta por aprovar', 'contas por aprovar'],
        'admin.quotas' => ['talão por validar', 'talões por validar'],
        'admin.requerimentos' => ['requerimento em análise', 'requerimentos em análise'],
        'admin.mensagens' => ['mensagem por ler', 'mensagens por ler'],
    ];
    // Cada entrada: [rota, rotulo, icone Material Symbols (resources/icons), rota para adicionar ou null, permissao ou null]
    $menu = [
        'Site público' => [
            ['admin.paginas', 'Páginas', 'description', 'admin.paginas.nova', 'conteudo.gerir'],
            ['admin.noticias', 'Notícias', 'newspaper', 'admin.noticias.nova', 'conteudo.gerir'],
            ['admin.eventos', 'Eventos', 'event', 'admin.eventos.novo', 'conteudo.gerir'],
            ['admin.slides', 'Carrossel', 'view_carousel', 'admin.slides.novo', 'conteudo.gerir'],
            ['admin.revistas', 'Revista', 'auto_stories', 'admin.revistas.nova', 'conteudo.gerir'],
            ['admin.videos', 'Vídeos', 'smart_display', 'admin.videos.novo', 'conteudo.gerir'],
            ['admin.parceiros', 'Parceiros', 'handshake', 'admin.parceiros.novo', 'conteudo.gerir'],
            ['admin.publicidades', 'Publicidade', 'campaign', 'admin.publicidades.novo', 'conteudo.gerir'],
        ],
        'Ficheiros' => [
            ['admin.imagens', 'Imagens', 'photo_library', null, 'conteudo.gerir'],
            ['admin.documentos', 'Documentos', 'folder_open', null, 'conteudo.gerir'],
        ],
        'Membros' => [
            ['admin.membros', 'Membros', 'group', null, 'membros.ver'],
            ['admin.quotas', 'Quotas', 'payments', null, 'financeiro.ver'],
            ['admin.requerimentos', 'Requerimentos', 'assignment', null, 'membros.ver'],
        ],
        'Comunicação' => [
            ['admin.mensagens', 'Mensagens', 'mail', null, 'comunicacao.gerir'],
            ['admin.newsletter', 'Newsletter', 'campaign', null, 'comunicacao.gerir'],
            ['admin.assistente', 'Assistente CDA', 'forum', null, 'conteudo.gerir'],
        ],
        'Sistema' => [
            ['admin.utilizadores', 'Utilizadores e acessos', 'admin_panel_settings', null, 'utilizadores.gerir'],
        ],
    ];
    $grupos = collect($menu)
        ->map(fn ($itens) => collect($itens)->filter(fn ($item) => ! $item[4] || $utilizador->pode($item[4]))->values())
        ->filter(fn ($itens) => $itens->isNotEmpty());
    $painelActivo = request()->routeIs('admin.painel');
@endphp
<body class="admin antialiased">
<header class="admin-barra">
    <button type="button" class="admin-barra-menu" data-admin-toggle aria-expanded="false" aria-controls="admin-lateral"><span class="mega-toggle-icone" aria-hidden="true"></span><span class="sr-only">Menu</span></button>
    <a href="{{ route('inicio') }}" class="admin-barra-site" target="_blank" rel="noopener">
        <img src="{{ asset('img/cda-logo.jpg') }}" alt="">
        <span>CDA Digital</span><small>Ver o site &nearr;</small>
    </a>
    <div class="admin-barra-fim">
        <span>Olá, <strong>{{ auth()->user()->name }}</strong><small class="admin-nivel">{{ auth()->user()->nivelLabel() }}</small></span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Sair</button></form>
    </div>
</header>

<div class="admin-corpo">
    <nav id="admin-lateral" class="admin-lateral" aria-label="Administração">
        <div class="admin-lateral-menu">
            <ul class="admin-lateral-inicio">
                <li class="{{ $painelActivo ? 'activo' : '' }}">
                    <div class="admin-item-linha">
                        <a href="{{ route('admin.painel') }}" title="Painel" @if ($painelActivo) aria-current="page" @endif>
                            <x-icone nome="dashboard" :cheio="$painelActivo" />
                            <span>Painel</span>
                        </a>
                    </div>
                </li>
            </ul>

            @foreach ($grupos as $grupo => $itens)
                @php
                    $grupoId = 'admin-grupo-'.\Illuminate\Support\Str::slug($grupo);
                    $grupoActivo = $itens->contains(fn ($item) => request()->routeIs($item[0], $item[0].'.*'));
                    $grupoPendentes = $itens->sum(fn ($item) => $porTratar[$item[0]] ?? 0);
                @endphp
                <div class="admin-lateral-seccao">
                    <button type="button" class="admin-lateral-grupo" data-admin-grupo aria-expanded="true" aria-controls="{{ $grupoId }}" @if ($grupoActivo) data-grupo-activo @endif>
                        <span>{{ $grupo }}</span>
                        @if ($grupoPendentes)<b class="admin-grupo-conta" title="Por tratar neste grupo">{{ $grupoPendentes }}</b>@endif
                        <x-icone nome="keyboard_arrow_down" />
                    </button>
                    <ul id="{{ $grupoId }}">
                        @foreach ($itens as [$rota, $rotulo, $icone, $novo])
                            @php $activo = request()->routeIs($rota, $rota.'.*'); $pendentes = $porTratar[$rota] ?? 0; @endphp
                            <li class="{{ $activo ? 'activo' : '' }}">
                                <div class="admin-item-linha">
                                    <a href="{{ route($rota) }}" title="{{ $rotulo }}" @if ($activo) aria-current="page" @endif>
                                        <x-icone :nome="$icone" :cheio="$activo" />
                                        <span>{{ $rotulo }}</span>
                                        @if ($pendentes)<b class="admin-conta" title="{{ $pendentes }} {{ $dicas[$rota][$pendentes === 1 ? 0 : 1] }}">{{ $pendentes }}</b>@endif
                                    </a>
                                    @if ($novo)
                                        <a href="{{ route($novo) }}" class="admin-item-novo @if (request()->routeIs($novo)) activo @endif" title="Adicionar em {{ $rotulo }}" aria-label="Adicionar em {{ $rotulo }}">
                                            <x-icone nome="add" />
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="admin-lateral-rodape">
            <a href="{{ route('inicio') }}" target="_blank" rel="noopener" title="Ver o site">
                <x-icone nome="open_in_new" />
                <span>Ver o site</span>
            </a>
            <button type="button" data-admin-recolher aria-label="Recolher menu" title="Recolher ou expandir menu">
                <x-icone nome="keyboard_double_arrow_left" />
                <span>Recolher menu</span>
            </button>
        </div>
    </nav>
    <div class="admin-lateral-fundo" data-admin-fundo hidden></div>

    <main class="admin-conteudo">
        @if (session('estado'))
            <div class="admin-aviso admin-aviso-ok" role="status">{{ session('estado') }}</div>
        @endif
        @if ($errors->any())
            <div class="admin-aviso admin-aviso-erro" role="alert">
                <ul>@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('conteudo')
    </main>
</div>
</body>
</html>
