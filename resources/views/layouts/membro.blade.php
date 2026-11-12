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
        ['membro.area', 'Resumo', 'dashboard'],
        ['membro.quotas', 'Quotas e pagamentos', 'payments'],
        ['membro.documentos', 'Documentos', 'description'],
        ['membro.requerimentos', 'Requerimentos', 'assignment'],
        ['membro.dados', 'Os meus dados', 'person'],
        ['membro.seguranca', 'Segurança', 'lock'],
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
                            <x-icone :nome="$icone" :cheio="$activo" />
                            <span>{{ $rotulo }}</span>
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="admin-lateral-grupo"><span>Portal</span></div>
        <ul>
            @if (\App\Models\Pagina::estaActiva('documentos'))
                <li><div class="admin-item-linha"><a href="{{ route('documentos') }}"><x-icone nome="folder_open" /><span>Centro documental</span></a></div></li>
            @endif
            @if (\App\Models\Pagina::estaActiva('contactos'))
                <li><div class="admin-item-linha"><a href="{{ route('contactos') }}"><x-icone nome="mail" /><span>Contactar a CDA</span></a></div></li>
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
