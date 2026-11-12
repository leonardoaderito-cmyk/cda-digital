@extends('layouts.app')
@section('conteudo')
@php
    $noticiasActivas = \App\Models\Pagina::estaActiva('noticias');
    $imagemDe = fn ($caminho) => \Illuminate\Support\Str::startsWith($caminho, ['http://', 'https://', '/']) ? $caminho : asset('storage/'.$caminho);
    // Carrossel: slides definidos em Administracao > Carrossel; sem nenhum activo, mostra as ultimas noticias
    $destaques = $slides->map(fn ($s) => [
        'etiqueta' => $s->etiqueta, 'titulo' => $s->titulo, 'texto' => $s->texto, 'imagem' => $s->imagem,
        'botao' => $s->botao_url ? ($s->botao_texto ?: 'Saber mais') : null, 'url' => $s->botao_url,
    ]);
    if ($destaques->isEmpty() && $noticiasActivas) {
        $destaques = $noticias->take(4)->map(fn ($n) => [
            'etiqueta' => $n->categoria ?: 'Notícia', 'titulo' => $n->titulo,
            'texto' => trim($n->data?->format('d/m/Y').' · '.\Illuminate\Support\Str::limit($n->resumo, 150), ' ·'),
            'imagem' => $n->imagem ? $imagemDe($n->imagem) : null, 'botao' => 'Ler mais', 'url' => route('noticia', $n->slug),
        ]);
    }
    $meses = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
@endphp

{{-- Autor: Leonardo (leonardoaderito-cmyk) - pagina inicial em duas colunas, cores solidas --}}
<div class="inicio-grelha">
    <div>
        @if ($destaques->isNotEmpty())
        <section data-carrossel class="carrossel mb-8" aria-label="Destaques">
            @foreach ($destaques as $d)
                <article data-slide class="slide {{ $d['imagem'] ? '' : 'slide-sem-imagem' }}">
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
                <h2>Flash de Notícias</h2>
                <a href="{{ route('noticias') }}">Ver todas</a>
            </div>
            <div class="flash">
                @forelse ($noticias as $n)
                    <article class="flash-item">
                        @if ($n->imagem)
                            <img src="{{ $imagemDe($n->imagem) }}" alt="" class="flash-miniatura" loading="lazy">
                        @else
                            <div class="flash-data" aria-hidden="true">
                                <strong>{{ $n->data?->format('d') ?? '--' }}</strong>
                                <span>{{ $n->data ? $meses[$n->data->month].' '.$n->data->format('y') : 'CDA' }}</span>
                            </div>
                        @endif
                        <div>
                            <a href="{{ route('noticia', $n->slug) }}">{{ $n->titulo }}</a>
                            <small>{{ $n->categoria }}@if ($n->categoria && $n->data) &middot; @endif{{ $n->data?->format('d/m/Y') }}</small>
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-cda-cinza">Ainda não há notícias publicadas.</p>
                @endforelse
            </div>
        </section>
        @endif
    </div>

    <aside>
        @if (\App\Models\Pagina::estaActiva('documentos'))
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2>Documentos</h2>
                <a href="{{ route('documentos') }}">Ver todos</a>
            </div>
            <ul class="lateral-lista">
                @foreach ($tiposDocumento as $tipo)
                    <li><a href="{{ route('documentos', ['tipo' => $tipo->tipo]) }}"><span>{{ $tipo->tipo }}</span><span class="lateral-conta">{{ $tipo->total }}</span></a></li>
                @endforeach
            </ul>
        </section>
        @endif

        @if (\App\Models\Pagina::estaActiva('eventos'))
        <section class="lateral-bloco">
            <div class="titulo-seccao">
                <h2>Eventos</h2>
                <a href="{{ route('eventos') }}">Ver todos</a>
            </div>
            <ul class="lateral-lista">
                @forelse ($eventos as $evento)
                    <li><a href="{{ route('evento', $evento->slug) }}"><span>{{ $evento->titulo }}</span><span class="lateral-conta">{{ $evento->data_evento?->format('d/m/Y') }}</span></a></li>
                @empty
                    <li class="py-2 text-cda-cinza">Consulte brevemente os próximos eventos da CDA.</li>
                @endforelse
            </ul>
        </section>
        @endif

        <section class="lateral-bloco">
            <div class="titulo-seccao"><h2>Membros</h2></div>
            <ul class="lateral-lista">
                <li><a href="{{ route('directorio') }}"><span>Directório de despachantes</span></a></li>
                <li><a href="{{ route('membro.area') }}"><span>Quotas e requerimentos</span></a></li>
                <li><a href="{{ route('registo') }}"><span>Como ser membro</span></a></li>
            </ul>
        </section>

        <section class="lateral-newsletter">
            <h2>Newsletter CDA</h2>
            <p>Receba notícias, eventos, circulares e comunicados da Câmara.</p>
            <form method="POST" action="{{ route('newsletter.subscrever') }}">
                @csrf
                <label class="sr-only" for="newsletter-nome">Nome</label>
                <input id="newsletter-nome" name="nome" value="{{ old('nome') }}" maxlength="160" placeholder="Nome (opcional)">
                <label class="sr-only" for="newsletter-email">Email</label>
                <input id="newsletter-email" type="email" name="email" value="{{ old('email') }}" required maxlength="200" placeholder="O seu email">
                <button>Subscrever</button>
            </form>
        </section>
    </aside>
</div>

<section class="acessos mt-10 mb-8" aria-label="Acesso rápido">
    @foreach ([['Documentos','Legislação, circulares e formulários','documentos','M6 2h9l5 5v15H6z'],['Directório','Despachantes aduaneiros registados','directorio','M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0zM4 21a8 8 0 0 1 16 0'],['Área do membro','Quotas e requerimentos','login','M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4'],['Contactos','Fale com a CDA','contactos','M4 4h16v13H8l-4 4z']] as [$t,$d,$rota,$icone])
        @continue($rota !== 'login' && ! \App\Models\Pagina::estaActiva($rota))
        <a href="{{ route($rota) }}" class="acesso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $icone }}"/></svg>
            <span><strong>{{ $t }}</strong><span>{{ $d }}</span></span>
        </a>
    @endforeach
</section>

<section class="numeros mb-10" aria-label="A CDA em números">
    @foreach ([['Membros activos', $totais['membros']], ['Documentos publicados', $totais['documentos']], ['Notícias', $totais['noticias']]] as [$r, $n])
        <div><strong data-contador="{{ $n }}">0</strong><span>{{ $r }}</span></div>
    @endforeach
</section>

<section>
    <div class="titulo-seccao">
        <h2>Parceiros</h2>
        <a href="{{ route('parceiros') }}">Ver todos</a>
    </div>
    <div class="parceiros">
        @foreach (['at.png' => 'Autoridade Tributária','mic.png' => 'Ministério da Indústria e Comércio','mef.png' => 'Ministério da Economia e Finanças','bancomoc.png' => 'Banco de Moçambique','cta.png' => 'CTA','apiex.png' => 'APIEX','wco.png' => 'Organização Mundial das Alfândegas'] as $logo => $nome)
            <img src="{{ asset('img/parceiros/'.$logo) }}" alt="{{ $nome }}" loading="lazy">
        @endforeach
    </div>
</section>
@endsection
