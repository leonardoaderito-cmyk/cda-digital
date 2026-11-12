<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo', 'Resumo') &lsaquo; Área do Membro CDA</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="theme-color" content="#000000">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Autor: Leonardo (leonardoaderito-cmyk) - area do membro: usa a mesma estrutura visual da administracao --}}
@php
    $eu = auth()->user();
    $menu = [
        ['membro.area', 'Resumo', 'M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z'],
        ['membro.quotas', 'Quotas e pagamentos', 'M3 6h18v12H3zM3 10h18M7 15h4'],
        ['membro.documentos', 'Documentos', 'M6 2h9l5 5v15H6zM14 2v6h6'],
        ['membro.requerimentos', 'Requerimentos', 'M5 3h14v18H5zM9 8h6M9 12h6M9 16h3'],
        ['membro.dados', 'Os meus dados', 'M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0'],
        ['membro.seguranca', 'Segurança', 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4'],
    ];
@endphp
<body class="admin membro antialiased">
<header class="admin-barra">
    <button type="button" class="admin-barra-menu" data-admin-toggle aria-expanded="false" aria-controls="admin-lateral"><span class="mega-toggle-icone" aria-hidden="true"></span><span class="sr-only">Menu</span></button>
    <a href="{{ route('inicio') }}" class="admin-barra-site">
        <img src="{{ asset('img/cda-logo.jpg') }}" alt="">
        <span>Área do Membro</span><small>Voltar ao portal &nearr;</small>
    </a>
    <div class="admin-barra-fim">
        <span>{{ $eu->name }}<small class="admin-nivel">N.º {{ $eu->carteira ?: '—' }}</small></span>
        @if ($eu->is_admin)<a href="{{ route('admin.painel') }}" class="admin-barra-ligacao">Administração</a>@endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button>Sair</button></form>
    </div>
</header>

<div class="admin-corpo">
    <nav id="admin-lateral" class="admin-lateral" aria-label="Área do Membro">
        <div class="admin-lateral-grupo"><span>A minha conta</span></div>
        <ul>
            @foreach ($menu as [$rota, $rotulo, $icone])
                @php $activo = request()->routeIs($rota); @endphp
                <li class="{{ $activo ? 'activo' : '' }}">
                    <div class="admin-item-linha">
                        <a href="{{ route($rota) }}" title="{{ $rotulo }}" @if ($activo) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="{{ $icone }}"/></svg>
                            <span>{{ $rotulo }}</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="admin-lateral-grupo"><span>Portal</span></div>
        <ul>
            @if (\App\Models\Pagina::estaActiva('documentos'))
                <li><div class="admin-item-linha"><a href="{{ route('documentos') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h6l2 3h8v13H4z"/></svg><span>Centro documental</span></a></div></li>
            @endif
            @if (\App\Models\Pagina::estaActiva('contactos'))
                <li><div class="admin-item-linha"><a href="{{ route('contactos') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 4h16v13H8l-4 4z"/></svg><span>Contactar a CDA</span></a></div></li>
            @endif
        </ul>
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
