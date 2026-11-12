@extends('layouts.admin')
@section('titulo', 'Painel')
@section('conteudo')
<div class="admin-cabecalho"><h1>Painel</h1></div>

<div class="admin-grelha">
    <section class="admin-caixa">
        <h2>Por tratar</h2>
        <ul class="admin-lista-num">
            @foreach ([['Contas pendentes de aprovação', $pendentes, 'admin.membros'], ['Requerimentos em análise', $requerimentos, 'admin.requerimentos'], ['Mensagens por ler', $mensagens, 'admin.mensagens']] as [$r, $n, $rota])
                <li><a href="{{ route($rota) }}"><strong class="{{ $n ? 'com-pendentes' : '' }}">{{ $n }}</strong><span>{{ $r }}</span></a></li>
            @endforeach
        </ul>
    </section>

    <section class="admin-caixa">
        <h2>Num relance</h2>
        <ul class="admin-relance">
            <li><a href="{{ route('admin.paginas') }}">{{ $paginas }} páginas</a>@if ($paginasInactivas) <small>({{ $paginasInactivas }} desactivadas)</small>@endif</li>
            <li><a href="{{ route('admin.noticias') }}">{{ $noticias }} notícias</a></li>
            <li><a href="{{ route('admin.eventos') }}">{{ $eventos }} eventos</a></li>
            <li><a href="{{ route('admin.imagens') }}">{{ $imagens }} imagens</a></li>
            <li><a href="{{ route('admin.documentos') }}">{{ $documentos }} documentos</a></li>
            <li><a href="{{ route('admin.newsletter') }}">{{ $subscritores }} subscritores</a></li>
        </ul>
    </section>

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

    <section class="admin-caixa admin-caixa-larga">
        <h2>Actividade recente na administração</h2>
        <ul class="admin-recentes">
            @forelse ($actividade as $a)
                <li><span>{{ \Illuminate\Support\Carbon::parse($a->created_at)->format('d/m/Y H:i') }}</span>{{ $utilizadores[$a->user_id] ?? 'Sistema' }} &middot; <code>{{ $a->accao }}</code>@if ($a->detalhe) <small>{{ $a->detalhe }}</small>@endif</li>
            @empty
                <li>Sem actividade registada.</li>
            @endforelse
        </ul>
    </section>
</div>
@endsection
