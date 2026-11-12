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
    $porTratar = [
        'admin.membros' => \App\Models\User::where('ativo', false)->where('is_admin', false)->count(),
        'admin.requerimentos' => \App\Models\Requerimento::where('estado', 'em_analise')->count(),
        'admin.mensagens' => \App\Models\ContactoMensagem::where('lida', false)->count(),
    ];
    // [rota, rotulo, icone (path SVG), sub-ligacoes [rota, rotulo]]
    $menu = [
        'Conteúdo' => [
            ['admin.painel', 'Painel', 'M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z', []],
            ['admin.paginas', 'Páginas', 'M6 2h9l5 5v15H6zM14 2v6h6', [['admin.paginas', 'Todas as páginas'], ['admin.paginas.nova', 'Adicionar nova']]],
            ['admin.slides', 'Carrossel', 'M6 5h12v14H6zM2 8v8M22 8v8', [['admin.slides', 'Todos os slides'], ['admin.slides.novo', 'Adicionar slide']]],
            ['admin.noticias', 'Notícias', 'M4 4h13v16H4zM17 8h3v12h-3M7 8h7M7 12h7M7 16h4', [['admin.noticias', 'Todas as notícias'], ['admin.noticias.nova', 'Adicionar nova']]],
            ['admin.eventos', 'Eventos', 'M4 6h16v15H4zM4 10h16M8 3v5M16 3v5', [['admin.eventos', 'Todos os eventos'], ['admin.eventos.novo', 'Adicionar novo']]],
            ['admin.imagens', 'Imagens', 'M3 5h18v14H3zM3 16l5-5 4 4 3-3 6 6M9 9.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0', []],
            ['admin.documentos', 'Documentos', 'M4 4h6l2 3h8v13H4z', []],
        ],
        'Membros' => [
            ['admin.membros', 'Contas', 'M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0', []],
            ['admin.quotas', 'Quotas', 'M3 6h18v12H3zM3 10h18M7 15h4', []],
            ['admin.requerimentos', 'Requerimentos', 'M5 3h14v18H5zM9 8h6M9 12h6M9 16h3', []],
        ],
        'Comunicação' => [
            ['admin.mensagens', 'Mensagens', 'M4 4h16v13H8l-4 4z', []],
            ['admin.newsletter', 'Newsletter', 'M3 5h18v14H3zM3 6l9 7 9-7', []],
        ],
    ];
@endphp
<body class="admin antialiased">
<header class="admin-barra">
    <button type="button" class="admin-barra-menu" data-admin-toggle aria-expanded="false" aria-controls="admin-lateral"><span class="mega-toggle-icone" aria-hidden="true"></span><span class="sr-only">Menu</span></button>
    <a href="{{ route('inicio') }}" class="admin-barra-site" target="_blank" rel="noopener">
        <img src="{{ asset('img/cda-logo.jpg') }}" alt="">
        <span>CDA Digital</span><small>Ver o site &nearr;</small>
    </a>
    <div class="admin-barra-fim">
        <span>Olá, <strong>{{ auth()->user()->name }}</strong></span>
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Sair</button></form>
    </div>
</header>

<div class="admin-corpo">
    <nav id="admin-lateral" class="admin-lateral" aria-label="Administração">
        @foreach ($menu as $grupo => $itens)
            <div class="admin-lateral-grupo">{{ $grupo }}</div>
            <ul>
                @foreach ($itens as [$rota, $rotulo, $icone, $sub])
                    @php $activo = request()->routeIs($rota, $rota.'.*'); @endphp
                    <li class="{{ $activo ? 'activo' : '' }}">
                        <a href="{{ route($rota) }}" @if ($activo) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                            <span>{{ $rotulo }}</span>
                            @if (! empty($porTratar[$rota]))<b class="admin-conta" title="Por tratar">{{ $porTratar[$rota] }}</b>@endif
                        </a>
                        @if ($activo && $sub)
                            <ul class="admin-sub">
                                @foreach ($sub as [$subRota, $subRotulo])
                                    <li><a href="{{ route($subRota) }}" class="{{ request()->routeIs($subRota) ? 'activo' : '' }}">{{ $subRotulo }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

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
