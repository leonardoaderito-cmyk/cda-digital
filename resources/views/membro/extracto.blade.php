@extends('layouts.membro')
@section('titulo', 'Extracto de conta')
@section('conteudo')
@php $mt = fn ($v) => number_format($v, 0, ',', '.').' MT'; @endphp
{{-- Autor: Leonardo (leonardoaderito-cmyk) - extracto de conta corrente: quotas lancadas (debito), pagamentos e isencoes (credito) e saldo --}}
<div class="admin-cabecalho">
    <div><h1>Extracto de conta</h1><p>Todos os movimentos da sua conta na CDA: quotas lançadas, pagamentos confirmados e saldo em cada momento.</p></div>
    <div class="admin-cabecalho-accoes">
        <a href="{{ route('membro.extracto.ver') }}" target="_blank" rel="noopener" class="admin-botao admin-botao-secundario"><x-icone nome="print" /> Imprimir</a>
        <a href="{{ route('membro.extracto.descarregar') }}" class="admin-botao"><x-icone nome="download" /> Descarregar PDF</a>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat"><span>Total lançado</span><strong>{{ $mt($totais['debito']) }}</strong><small>{{ $quotas->count() }} {{ $quotas->count() === 1 ? 'quota' : 'quotas' }}</small></div>
    <div class="admin-stat ok"><span>Total pago e isento</span><strong>{{ $mt($totais['credito']) }}</strong></div>
    <div class="admin-stat {{ $totais['saldo'] > 0 ? 'destaque' : 'ok' }}"><span>Saldo em dívida</span><strong>{{ $mt($totais['saldo']) }}</strong><small>{{ $totais['saldo'] > 0 ? 'Por regularizar' : 'Sem valores em dívida' }}</small></div>
    <div class="admin-stat"><span>Talões em validação</span><strong>{{ $emValidacao }}</strong><small>não contam ainda no saldo</small></div>
</div>

@if ($movimentos->isEmpty())
    <div class="admin-vazio"><strong>Ainda não há movimentos</strong>Quando a CDA lançar a sua quota anual, os movimentos aparecem aqui.</div>
@else
<div class="admin-tabela-caixa">
<table class="admin-tabela membro-extracto">
    <thead><tr><th>Data</th><th>Descrição</th><th>Referência</th><th class="num">Débito</th><th class="num">Crédito</th><th class="num">Saldo</th></tr></thead>
    <tbody>
        @foreach ($movimentos as $m)
            <tr>
                <td class="membro-extracto-data">{{ $m['data']->format('d/m/Y') }}</td>
                <td><strong>{{ $m['descricao'] }}</strong></td>
                <td>{{ $m['referencia'] ?: '—' }}</td>
                <td class="num" data-rotulo="Débito">{{ $m['debito'] ? $mt($m['debito']) : '' }}</td>
                <td class="num credito" data-rotulo="Crédito">{{ $m['credito'] ? $mt($m['credito']) : '' }}</td>
                <td class="num" data-rotulo="Saldo"><strong @class(['perigo' => $m['saldo'] > 0])>{{ $mt($m['saldo']) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot><tr><td colspan="3">Totais</td><td class="num">{{ $mt($totais['debito']) }}</td><td class="num">{{ $mt($totais['credito']) }}</td><td class="num">{{ $mt($totais['saldo']) }}</td></tr></tfoot>
</table>
</div>
<p class="admin-nota">O débito é a quota lançada para o ano; o crédito é o pagamento confirmado pela secretaria (ou a isenção). Um talão enviado só entra no extracto depois de validado.</p>
@endif
@endsection
