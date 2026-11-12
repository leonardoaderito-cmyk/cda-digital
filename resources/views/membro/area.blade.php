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
        <p>O seu certificado de quitação de {{ now()->year }} está disponível, pronto para imprimir e pode ser confirmado por terceiros através do código QR.</p>
        <div class="admin-atalhos">
            <a href="{{ route('membro.declaracao.descarregar') }}" class="admin-botao">Emitir certificado (PDF)</a>
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
        <p>Pague a quota e envie o talão para a secretaria validar. Com as quotas em dia pode emitir o certificado de quitação e o cartão de membro.</p>
        <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-botao">Pagar e enviar talão</a></div>
    </section>
@endif

<div class="admin-grelha">
    <section class="admin-caixa">
        <h2>Últimas quotas</h2>
        @if ($quotas->isEmpty())
            <p>Ainda não existem quotas registadas na sua conta.</p>
        @else
            <ul class="admin-recentes">
                @foreach ($quotas->take(4) as $q)
                    <li><span>{{ $q->ano }}</span><strong>{{ $mt($q->valor) }}</strong> @include('membro._estado', ['q' => $q])</li>
                @endforeach
            </ul>
            <div class="admin-atalhos"><a href="{{ route('membro.quotas') }}" class="admin-ligacao">Ver todas as quotas &rarr;</a></div>
        @endif
    </section>

    <section class="admin-caixa">
        <h2>Requerimentos</h2>
        @if ($requerimentos->isEmpty())
            <p>Não tem requerimentos. Pode pedir declarações, segundas vias e outros serviços à CDA.</p>
        @else
            <ul class="admin-recentes">
                @foreach ($requerimentos as $r)
                    <li><span>{{ $r->created_at->format('d/m/Y') }}</span>{{ $r->tipo }} <span class="admin-estado {{ ['em_analise' => 'aviso', 'aprovado' => 'ligado', 'rejeitado' => 'alerta'][$r->estado] ?? 'neutro' }}" style="min-width:0;color:#fff;margin:0">{{ ['em_analise' => 'Em análise', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'][$r->estado] ?? $r->estado }}</span></li>
                @endforeach
            </ul>
        @endif
        <div class="admin-atalhos"><a href="{{ route('membro.requerimentos') }}" class="admin-ligacao">{{ $requerimentos->isEmpty() ? 'Fazer um requerimento' : 'Ver todos e fazer novo' }} &rarr;</a></div>
    </section>

    @if ($comunicados->isNotEmpty())
    <section class="admin-caixa">
        <h2>Circulares e comunicados recentes</h2>
        <ul class="admin-recentes">
            @foreach ($comunicados as $d)
                <li><span>{{ $d->tipo }} {{ $d->ano }}</span><a href="{{ route('documento.abrir', $d) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($d->titulo, 90) }}</a></li>
            @endforeach
        </ul>
        <div class="admin-atalhos"><a href="{{ route('documentos') }}" class="admin-ligacao">Centro documental &rarr;</a></div>
    </section>
    @endif

    @if ($eventos->isNotEmpty())
    <section class="admin-caixa">
        <h2>Próximos eventos</h2>
        <ul class="admin-recentes">
            @foreach ($eventos as $e)
                <li><span>{{ $e->data_evento?->format('d/m/Y') }}</span><a href="{{ route('evento', $e->slug) }}">{{ $e->titulo }}</a>@if ($e->local) <small>&middot; {{ $e->local }}</small>@endif</li>
            @endforeach
        </ul>
    </section>
    @endif
</div>
@endsection
