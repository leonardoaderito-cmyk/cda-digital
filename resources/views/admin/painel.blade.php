@extends('layouts.admin')
@section('titulo', 'Painel')
@section('conteudo')
<div class="admin-cabecalho"><div><h1>Painel</h1><p class="text-sm text-zinc-600">{{ auth()->user()->nivelLabel() }}</p></div></div>

<div class="admin-grelha">
    @if(auth()->user()->pode('membros.ver') || auth()->user()->pode('comunicacao.gerir'))
    <section class="admin-caixa">
        <h2>Por tratar</h2>
        <ul class="admin-lista-num">
            @foreach ([['Contas pendentes de aprovação', $pendentes, 'admin.membros', 'membros.ver'], ['Requerimentos em análise', $requerimentos, 'admin.requerimentos', 'membros.ver'], ['Mensagens por ler', $mensagens, 'admin.mensagens', 'comunicacao.gerir']] as [$r, $n, $rota, $permissao])
                @if(auth()->user()->pode($permissao))
                <li><a href="{{ route($rota) }}"><strong class="{{ $n ? 'com-pendentes' : '' }}">{{ $n }}</strong><span>{{ $r }}</span></a></li>
                @endif
            @endforeach
        </ul>
    </section>
    @endif

    @if(auth()->user()->pode('conteudo.gerir') || auth()->user()->pode('comunicacao.gerir'))
    <section class="admin-caixa">
        <h2>Num relance</h2>
        <ul class="admin-relance">
            @if(auth()->user()->pode('conteudo.gerir'))
            <li><a href="{{ route('admin.paginas') }}">{{ $paginas }} páginas</a>@if ($paginasInactivas) <small>({{ $paginasInactivas }} desactivadas)</small>@endif</li>
            <li><a href="{{ route('admin.noticias') }}">{{ $noticias }} notícias</a></li>
            <li><a href="{{ route('admin.eventos') }}">{{ $eventos }} eventos</a></li>
            <li><a href="{{ route('admin.imagens') }}">{{ $imagens }} imagens</a></li>
            <li><a href="{{ route('admin.documentos') }}">{{ $documentos }} documentos</a></li>
            @endif
            @if(auth()->user()->pode('comunicacao.gerir'))
            <li><a href="{{ route('admin.newsletter') }}">{{ $subscritores }} subscritores</a></li>
            @endif
        </ul>
    </section>
    @endif

    @if(auth()->user()->pode('conteudo.gerir'))
    <section class="admin-caixa">
        <h2>Atalhos</h2>
        <div class="admin-atalhos">
            <a href="{{ route('admin.paginas.nova') }}" class="admin-botao">Nova página</a>
            <a href="{{ route('admin.noticias.nova') }}" class="admin-botao">Nova notícia</a>
            <a href="{{ route('admin.eventos.novo') }}" class="admin-botao">Novo evento</a>
            <a href="{{ route('admin.imagens') }}" class="admin-botao admin-botao-secundario">Carregar imagens</a>
            <a href="{{ route('admin.documentos') }}" class="admin-botao admin-botao-secundario">Publicar documento</a>
        </div>
    </section>

    <section class="admin-caixa">
        <h2>Notícias recentes</h2>
        <ul class="admin-recentes">
            @forelse ($ultimasNoticias as $n)
                <li><span>{{ $n->data?->format('d/m/Y') }}</span><a href="{{ route('admin.noticias.editar', $n) }}">{{ $n->titulo }}</a>@unless ($n->publicada) <small>— oculta</small>@endunless</li>
            @empty
                <li>Ainda não há notícias.</li>
            @endforelse
        </ul>
    </section>
    @endif

</div>
@endsection
