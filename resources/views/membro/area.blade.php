@extends('layouts.membro')
@section('titulo', 'Resumo')
@section('conteudo')
@php $mt = fn ($v) => number_format($v, 0, ',', '.').' MT'; @endphp
<div class="admin-cabecalho">
    <div><h1>Olá, {{ \Illuminate\Support\Str::before($membro->name, ' ') }}</h1><p>N.º de membro {{ $membro->carteira ?? '—' }}@if ($registo?->empresa) &middot; {{ $registo->empresa }}@endif @if ($membro->ultimo_login) &middot; última entrada em {{ $membro->ultimo_login->format('d/m/Y H:i') }}@endif</p></div>
</div>

<div class="admin-stats">
    <div class="admin-stat {{ $regular ? 'ok' : 'destaque' }}"><span>Situação financeira</span><strong>{{ $situacao }}</strong></div>
    <div class="admin-stat"><span>Quota de {{ now()->year }}</span><strong>{{ $quotaDoAno ? $mt($quotaDoAno->valor) : '—' }}</strong><small>@if ($quotaDoAno) @include('membro._estado', ['q' => $quotaDoAno]) @else Ainda não lançada @endif</small></div>
    <div class="admin-stat {{ $totalPendente ? 'destaque' : '' }}"><span>Por pagar</span><strong>{{ $mt($totalPendente) }}</strong></div>
    <div class="admin-stat"><span>Total pago</span><strong>{{ $mt($totalPago) }}</strong></div>
</div>

@if ($regular)
    <section class="admin-caixa membro-destaque ok">
        <h2>Quotas em dia</h2>
        <p>A sua declaração de quitação de {{ now()->year }} está disponível: tem o selo da CDA, um código único e pode ser confirmada por terceiros através do código QR.</p>
        <div class="admin-atalhos">
            <a href="{{ route('membro.declaracao.descarregar') }}" class="admin-botao">Emitir declaração (PDF)</a>
            <a href="{{ route('membro.declaracao') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario">Pré-visualizar</a>
            @if ($membro->carteira && ! $membro->is_admin)<a href="{{ route('membro.cartao') }}" class="admin-botao admin-botao-secundario">Cartão de membro</a>@endif
        </div>
    </section>
@elseif ($emValidacao)
    <section class="admin-caixa membro-destaque">
        <h2>Pagamento em validação</h2>
        <p>Recebemos {{ $emValidacao === 1 ? 'o seu talão' : 'os seus '.$emValidacao.' talões' }}. A situação fica regularizada assim que a secretaria confirmar o pagamento.</p>
        <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-botao admin-botao-secundario">Ver quotas</a></div>
    </section>
@elseif ($totalPendente || $quotas->whereIn('estado', ['pendente', 'em_atraso'])->isNotEmpty())
    <section class="admin-caixa membro-destaque alerta">
        <h2>Tem quotas por regularizar</h2>
        <p>Pague a quota e envie o talão para a secretaria validar. Com as quotas em dia pode emitir a declaração de quitação e o cartão de membro.</p>
        <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-botao">Pagar e enviar talão</a></div>
    </section>
@endif

@php
    $podeDeclarar = $regular && ! $membro->is_admin;
    $servicos = array_filter([
        ['Declaração de quitação', $podeDeclarar ? 'Emitir com selo e código QR' : 'Disponível com as quotas em dia', 'workspace_premium', $podeDeclarar ? route('membro.documentos') : route('membro.quotas'), $podeDeclarar],
        ['Extracto de conta', 'Movimentos, pagamentos e saldo', 'receipt_long', route('membro.extracto'), true],
        ['Pagar quota', 'Enviar o talão de pagamento', 'upload_file', route('membro.quotas'), true],
        ['Novo requerimento', 'Pedir um serviço à CDA', 'post_add', route('membro.requerimentos'), true],
        $membro->carteira && ! $membro->is_admin ? ['Cartão de membro', 'Com código de verificação', 'badge', route('membro.cartao'), true] : null,
        ['Os meus dados', 'Contactos e entidade', 'person', route('membro.dados'), true],
    ]);
@endphp
<h2 class="admin-seccao">Serviços</h2>
<nav class="membro-servicos" aria-label="Serviços da área do membro">
    @foreach ($servicos as [$titulo, $texto, $icone, $url, $disponivel])
        <a href="{{ $url }}" @class(['membro-servico', 'indisponivel' => ! $disponivel])>
            <x-icone :nome="$icone" />
            <span><strong>{{ $titulo }}</strong><small>{{ $texto }}</small></span>
        </a>
    @endforeach
</nav>

<div class="membro-paineis">
    <section class="admin-caixa">
        <h2>Últimas quotas</h2>
        @if ($quotas->isEmpty())
            <p>Ainda não existem quotas registadas na sua conta.</p>
        @else
            <table class="admin-mini-tabela">
                <thead><tr><th>Ano</th><th>Estado</th><th class="num">Valor</th></tr></thead>
                <tbody>
                    @foreach ($quotas->take(4) as $q)
                        <tr><td><strong>{{ $q->ano }}</strong></td><td>@include('membro._estado', ['q' => $q])</td><td class="num">{{ $mt($q->valor) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-ligacao">Ver todas as quotas &rarr;</a><a href="{{ route('membro.extracto') }}" class="admin-ligacao">Extracto de conta &rarr;</a></div>
        @endif
    </section>

    <section class="admin-caixa">
        <h2>Requerimentos</h2>
        @if ($requerimentos->isEmpty())
            <p>Não tem requerimentos. Pode pedir declarações, segundas vias e outros serviços à CDA.</p>
        @else
            <ul class="admin-recentes">
                @foreach ($requerimentos as $r)
                    <li><span>{{ $r->created_at->format('d/m/Y') }}</span>{{ $r->tipo }} <span class="admin-estado {{ ['em_analise' => 'aviso', 'aprovado' => 'ligado', 'rejeitado' => 'alerta'][$r->estado] ?? 'neutro' }}">{{ ['em_analise' => 'Em análise', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'][$r->estado] ?? $r->estado }}</span></li>
                @endforeach
            </ul>
        @endif
        <div class="admin-atalhos"><a href="{{ route('membro.requerimentos') }}" class="admin-ligacao">{{ $requerimentos->isEmpty() ? 'Fazer um requerimento' : 'Ver todos e fazer novo' }} &rarr;</a></div>
    </section>

    @if ($comunicados->isNotEmpty())
    <section class="admin-caixa">
        <h2>Circulares e comunicados recentes</h2>
        {{-- O titulo vem como "Circular 05/AT/DGA/411/2025 — Assunto": a referencia vai para a linha de cima e o assunto fica em destaque --}}
        <ul class="membro-lista">
            @foreach ($comunicados as $d)
                @php
                    [$referencia, $assunto] = array_pad(preg_split('/\s+[—–-]\s+/u', trim($d->titulo), 2), 2, null);
                    $referencia = trim(\Illuminate\Support\Str::after($referencia, $d->tipo)) ?: $d->ano;
                @endphp
                <li>
                    <a href="{{ route('documento.abrir', $d) }}" target="_blank" rel="noopener">
                        <x-icone nome="description" />
                        <span class="membro-lista-texto">
                            <small>{{ $d->tipo }} &middot; {{ $assunto ? $referencia : $d->ano }}</small>
                            <strong>{{ \Illuminate\Support\Str::ucfirst($assunto ?: $d->titulo) }}</strong>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="admin-atalhos"><a href="{{ route('documentos') }}" class="admin-ligacao">Ver todos no centro documental &rarr;</a></div>
    </section>
    @endif

    @if ($eventos->isNotEmpty())
    <section class="admin-caixa">
        <h2>Próximos eventos</h2>
        <ul class="membro-lista">
            @php $mesesCurtos = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez']; @endphp
            @foreach ($eventos as $e)
                <li>
                    <a href="{{ route('evento', $e->slug) }}">
                        <span class="membro-lista-data"><strong>{{ $e->data_evento?->format('d') }}</strong><small>{{ $e->data_evento ? $mesesCurtos[$e->data_evento->month] : '' }}</small></span>
                        <span class="membro-lista-texto">
                            <small>{{ $e->local ?: 'Local a anunciar' }}</small>
                            <strong>{{ $e->titulo }}</strong>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
    @endif
</div>
@endsection
