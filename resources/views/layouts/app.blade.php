<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'CDA Digital') - Câmara dos Despachantes Aduaneiros de Moçambique</title>
    {{-- Autor: Leonardo (leonardoaderito-cmyk) - identidade nos navegadores e dispositivos móveis --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#000000">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen flex flex-col">
<header id="topo">
    <div class="barra-topo">
        <div class="max-w-6xl mx-auto px-4 py-1.5 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
            <span><a href="tel:+25821305504">+258 21 305 504</a> &middot; Maputo, Moçambique</span>
            <div class="flex items-center gap-4">
                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.painel') }}">Administração</a>
                    @endif
                    <a href="{{ route('membro.area') }}">{{ auth()->user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="botao-topo">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Entrar</a>
                    <a href="{{ route('registo') }}" class="botao-topo">Registar</a>
                @endauth
            </div>
        </div>
    </div>
    <div class="cabecalho">
        <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
            <a href="{{ route('inicio') }}" class="flex items-center gap-3">
                <img src="{{ asset('img/cda-logo.jpg') }}" alt="Logotipo da CDA" class="h-20 w-20 object-contain">
                <span class="cabecalho-nome">Câmara dos Despachantes<br>Aduaneiros de Moçambique
                    <span class="cabecalho-lema">&ldquo;Por uma câmara coesa, interventiva e próspera&rdquo;</span>
                </span>
            </a>
            <form method="GET" action="{{ route('documentos') }}" class="pesquisa" role="search">
                <label class="sr-only" for="pesquisa-topo">Pesquisar documentos</label>
                <input id="pesquisa-topo" type="search" name="q" placeholder="Pesquisar documentos">
                <button>Procurar</button>
            </form>
        </div>
    </div>
    <div class="mega-barra">
        <div class="max-w-6xl mx-auto px-4">
            @include('layouts._mega')
        </div>
    </div>
</header>

<main class="flex-1 w-full max-w-6xl mx-auto px-4 py-8">
    @if (session('estado'))
        <div class="mb-6 rounded border border-green-300 bg-green-50 text-green-900 px-4 py-3">{{ session('estado') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded border border-red-300 bg-red-50 text-red-900 px-4 py-3">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('conteudo')
</main>

@include('layouts._rodape')
@include('layouts._cookies')
</body>
</html>
