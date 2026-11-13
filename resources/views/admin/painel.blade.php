@extends('layouts.admin')
@section('titulo', 'Painel')
@section('conteudo')
@php
    // Autor: Leonardo (leonardoaderito-cmyk) - painel inicial: o que ha para tratar, situacao financeira do ano, conteudo e atalhos
    $eu = auth()->user();
    $hora = (int) now()->format('G');
    $saudacao = $hora < 12 ? 'Bom dia' : ($hora < 19 ? 'Boa tarde' : 'Boa noite');
    $meses = [1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    $mt = fn ($v) => number_format($v, 0, ',', '.').' MT';
    $plural = fn ($n, $um, $varios) => $n === 1 ? $um : $varios;

    // Cada tarefa: [numero, rotulo (singular, plural), icone, rota, permissao]
    $tarefas = array_filter([
        [$pendentes, $plural($pendentes, 'Conta por aprovar', 'Contas por aprovar'), 'person_add', 'admin.membros', 'membros.ver'],
        [$taloes, $plural($taloes, 'Talão por validar', 'Talões por validar'), 'payments', 'admin.quotas', 'financeiro.ver'],
        [$requerimentos, $plural($requerimentos, 'Requerimento em análise', 'Requerimentos em análise'), 'assignment', 'admin.requerimentos', 'membros.ver'],
        [$mensagens, $plural($mensagens, 'Mensagem por ler', 'Mensagens por ler'), 'mail', 'admin.mensagens', 'comunicacao.gerir'],
    ], fn ($t) => $eu->pode($t[4]));

    $criar = [
        ['Nova notícia', 'newspaper', 'admin.noticias.nova'],
        ['Novo evento', 'event', 'admin.eventos.novo'],
        ['Nova página', 'description', 'admin.paginas.nova'],
        ['Novo slide do carrossel', 'view_carousel', 'admin.slides.novo'],
        ['Publicar documento', 'upload_file', 'admin.documentos'],
        ['Carregar imagens', 'photo_library', 'admin.imagens'],
    ];
    $conteudo = array_filter([
        $eu->pode('conteudo.gerir') ? ['Páginas', $paginas, 'admin.paginas', $paginasInactivas ? $paginasInactivas.' desactivadas' : null] : null,
        $eu->pode('conteudo.gerir') ? ['Notícias', $noticias, 'admin.noticias', null] : null,
        $eu->pode('conteudo.gerir') ? ['Eventos', $eventos, 'admin.eventos', null] : null,
        $eu->pode('conteudo.gerir') ? ['Documentos', $documentos, 'admin.documentos', null] : null,
        $eu->pode('conteudo.gerir') ? ['Imagens', $imagens, 'admin.imagens', null] : null,
        $eu->pode('comunicacao.gerir') ? ['Subscritores da newsletter', $subscritores, 'admin.newsletter', null] : null,
        $eu->pode('membros.ver') ? ['Membros com conta activa', $membrosActivos, 'admin.membros', null] : null,
    ]);
    $percentagem = $quotasAno['total'] ? (int) round($quotasAno['pagas'] / $quotasAno['total'] * 100) : 0;
@endphp

<div class="admin-cabecalho">
    <div>
        <h1>{{ $saudacao }}, {{ \Illuminate\Support\Str::before($eu->name, ' ') ?: $eu->name }}</h1>
        <p>{{ $eu->nivelLabel() }} &middot; {{ now()->format('j') }} de {{ $meses[now()->month] }} de {{ now()->year }}</p>
    </div>
    <div class="admin-cabecalho-accoes"><a href="{{ route('inicio') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario"><x-icone nome="open_in_new" /> Ver o site</a></div>
</div>

@if ($tarefas)
    <h2 class="admin-seccao">Por tratar</h2>
    <div class="painel-tarefas">
        @foreach ($tarefas as [$numero, $rotulo, $icone, $rota])
            <a href="{{ route($rota) }}" @class(['painel-tarefa', 'pendente' => $numero > 0])>
                <x-icone :nome="$icone" />
                <span class="painel-tarefa-texto"><strong>{{ $numero }}</strong><small>{{ $rotulo }}</small></span>
            </a>
        @endforeach
    </div>
@endif

<div class="painel-colunas">
    <div class="painel-principal">
        @if ($eu->pode('financeiro.ver'))
        <section class="admin-caixa">
            <h2>Quotas de {{ now()->year }}</h2>
            @if ($quotasAno['total'])
                <div class="painel-progresso" role="img" aria-label="{{ $percentagem }}% das quotas de {{ now()->year }} regularizadas">
                    <span style="width: {{ $percentagem }}%"></span>
                </div>
                <p class="painel-progresso-legenda"><strong>{{ $quotasAno['pagas'] }} de {{ $quotasAno['total'] }}</strong> quotas regularizadas ({{ $percentagem }}%)</p>
                <div class="painel-numeros">
                    <div><small>Recebido</small><strong class="ok">{{ $mt($quotasAno['recebido']) }}</strong></div>
                    <div><small>Por receber</small><strong @class(['perigo' => $quotasAno['porReceber'] > 0])>{{ $mt($quotasAno['porReceber']) }}</strong></div>
                    <div><small>Membros activos</small><strong>{{ $membrosActivos }}</strong></div>
                </div>
            @else
                <p>A quota de {{ now()->year }} ainda não foi lançada.</p>
            @endif
            <div class="admin-atalhos"><a href="{{ route('admin.quotas') }}" class="admin-ligacao">Gerir quotas &rarr;</a></div>
        </section>
        @endif

        @if ($eu->pode('conteudo.gerir'))
        <section class="admin-caixa">
            <h2>Notícias recentes</h2>
            <ul class="admin-noticias-recentes">
                @forelse ($ultimasNoticias as $n)
                    <li>
                        <div class="admin-noticia-miniatura">
                            @if ($n->imagem)<img src="{{ $n->imagem }}" alt="" loading="lazy">@else<span aria-hidden="true">CDA</span>@endif
                        </div>
                        <time datetime="{{ $n->data?->toDateString() }}">{{ $n->data?->format('d/m/Y') ?: 'Sem data' }}</time>
                        <div class="admin-noticia-info">
                            <a href="{{ route('admin.noticias.editar', $n) }}">{{ $n->titulo }}</a>
                            <small>{{ $n->categoria ?: 'Sem categoria' }}</small>
                        </div>
                        <span class="admin-estado {{ $n->publicada ? 'ligado' : 'neutro' }}">{{ $n->publicada ? 'Publicada' : 'Oculta' }}</span>
                    </li>
                @empty
                    <li class="admin-noticia-vazia">Ainda não há notícias.</li>
                @endforelse
            </ul>
            <div class="admin-atalhos"><a href="{{ route('admin.noticias') }}" class="admin-ligacao">Ver todas as notícias &rarr;</a></div>
        </section>
        @endif
    </div>

    <aside class="painel-lado">
        @if ($eu->pode('conteudo.gerir'))
        <section class="admin-caixa">
            <h2>Criar</h2>
            <ul class="admin-ligacoes">
                @foreach ($criar as [$rotulo, $icone, $rota])
                    <li><a href="{{ route($rota) }}"><x-icone :nome="$icone" /><span>{{ $rotulo }}</span><x-icone nome="add" class="admin-ligacoes-fim" /></a></li>
                @endforeach
            </ul>
        </section>
        @endif

        @if ($conteudo)
        <section class="admin-caixa">
            <h2>Num relance</h2>
            <ul class="admin-ligacoes admin-ligacoes-numeros">
                @foreach ($conteudo as [$rotulo, $numero, $rota, $nota])
                    <li><a href="{{ route($rota) }}"><span>{{ $rotulo }}@if ($nota)<small>{{ $nota }}</small>@endif</span><strong>{{ number_format($numero, 0, ',', '.') }}</strong></a></li>
                @endforeach
            </ul>
        </section>
        @endif
    </aside>
</div>
@endsection
