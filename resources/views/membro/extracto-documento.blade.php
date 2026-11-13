@extends('layouts.documento')
@section('titulo', 'Extracto '.$codigo)
@section('referencia-rotulo', 'Extracto n.º')
@php $mt = fn ($v) => number_format($v, 0, ',', '.'); @endphp
@section('estilos')
    .lista td { font-size: 9.5px; }
    .lista .saldo-devedor { color: #b5121b; }
    .saldo-final { width: 100%; border-collapse: collapse; margin-top: 14px; }
    .saldo-final td { width: 25%; padding: 8px 10px; border: 1px solid #cfcfcf; }
    .saldo-final span { display: block; color: #666666; font-size: 7.5px; letter-spacing: .06em; text-transform: uppercase; }
    .saldo-final strong { font-size: 12px; }
    .saldo-final .destaque { background: #111111; color: #ffffff; border-color: #111111; }
    .saldo-final .destaque span { color: #cfcfcf; }
@endsection
@section('conteudo')
    {{-- Autor: Leonardo (leonardoaderito-cmyk) - extracto de conta corrente do membro (quotas lancadas e pagamentos) --}}
    <h1 class="titulo">Extracto de Conta Corrente</h1>
    <p class="subtitulo">Quotas e pagamentos @if ($periodo) &middot; {{ $periodo }} @endif</p>

    <table class="quadro" role="presentation"><tr>
        <td><span>Membro</span><strong>{{ $membro->name }}</strong></td>
        <td><span>Número de membro</span><strong>{{ $membro->carteira ?: '—' }}</strong></td>
        <td><span>Emitido em</span><strong>{{ now()->format('d/m/Y H:i') }}</strong></td>
    </tr></table>

    <table class="lista">
        <thead><tr><th>Data</th><th>Descrição</th><th>Referência</th><th class="num">Débito (MT)</th><th class="num">Crédito (MT)</th><th class="num">Saldo (MT)</th></tr></thead>
        <tbody>
            @forelse ($movimentos as $m)
                <tr>
                    <td>{{ $m['data']->format('d/m/Y') }}</td>
                    <td>{{ $m['descricao'] }}</td>
                    <td>{{ $m['referencia'] ?: '—' }}</td>
                    <td class="num">{{ $m['debito'] ? $mt($m['debito']) : '' }}</td>
                    <td class="num">{{ $m['credito'] ? $mt($m['credito']) : '' }}</td>
                    <td class="num {{ $m['saldo'] > 0 ? 'saldo-devedor' : '' }}">{{ $mt($m['saldo']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Sem movimentos registados.</td></tr>
            @endforelse
        </tbody>
        @if ($movimentos->isNotEmpty())
            <tfoot><tr><td colspan="3">Totais</td><td class="num">{{ $mt($totais['debito']) }}</td><td class="num">{{ $mt($totais['credito']) }}</td><td class="num">{{ $mt($totais['saldo']) }}</td></tr></tfoot>
        @endif
    </table>

    <table class="saldo-final" role="presentation"><tr>
        <td><span>Total lançado</span><strong>{{ $mt($totais['debito']) }} MT</strong></td>
        <td><span>Total pago e isento</span><strong>{{ $mt($totais['credito']) }} MT</strong></td>
        <td><span>Talões em validação</span><strong>{{ $emValidacao }}</strong></td>
        <td class="destaque"><span>Saldo em dívida</span><strong>{{ $mt($totais['saldo']) }} MT</strong></td>
    </tr></table>

    <table class="fecho" role="presentation" style="table-layout: auto"><tr>
        <td class="fecho-qr"><img src="{{ $qrCode }}" alt="Código QR de verificação"></td>
        <td class="fecho-verificacao">
            <p><strong>VERIFICAÇÃO ELECTRÓNICA</strong></p>
            <p>Leia o código QR para confirmar a situação financeira actual deste membro na plataforma CDA Digital. Os valores deste extracto reflectem os registos à data de emissão.</p>
            <p>Código: <strong>{{ $codigo }}</strong></p>
        </td>
    </tr></table>
@endsection
@section('rodape', 'Extracto emitido electronicamente pela plataforma CDA Digital. Para esclarecimentos sobre os valores contacte a Tesouraria da CDA.')
@section('accoes')
    <a href="{{ $urlVoltar ?? route('membro.extracto') }}" class="voltar">&larr; Voltar</a>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="{{ $urlPdf ?? route('membro.extracto.descarregar') }}">Descarregar PDF</a>
@endsection
