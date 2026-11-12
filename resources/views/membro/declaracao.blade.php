<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Declaração de quitação {{ $codigo }}</title>
    <style>
        @page{margin:20mm}body{font-family:"DejaVu Sans",Arial,sans-serif;color:#18181b;background:#f4f4f5;margin:0;padding:32px}.folha{max-width:760px;margin:auto;background:#fff;padding:54px;border:1px solid #d4d4d8}.cab{text-align:center;border-bottom:3px solid #172554;padding-bottom:24px}.cab img{width:82px;height:82px;object-fit:cover}.cab h1{font-size:21px;margin:12px 0 5px}.cab p{margin:0;color:#52525b}.titulo{text-align:center;margin:42px 0 30px;text-transform:uppercase;letter-spacing:.06em}.texto{font-size:15px;line-height:1.8;text-align:justify}.resumo{width:100%;border-collapse:collapse;margin:28px 0}.resumo th,.resumo td{border:1px solid #d4d4d8;padding:9px;text-align:left;font-size:12px}.resumo th{background:#f4f4f5}.validacao{margin-top:30px;border-top:1px solid #d4d4d8;padding-top:20px}.validacao img{width:125px;height:125px;float:right;margin-left:24px}.validacao p{font-size:12px;color:#52525b}.limpar{clear:both}.assinatura{margin-top:40px;text-align:center}.assinatura span{display:inline-block;border-top:1px solid #18181b;padding:8px 70px 0}.accoes{text-align:center;margin:22px}.accoes a,.accoes button{display:inline-block;background:#172554;color:#fff;border:0;border-radius:5px;padding:10px 18px;text-decoration:none;margin:0 4px;cursor:pointer}@media print{body{background:#fff;padding:0}.folha{border:0;padding:0}.accoes{display:none}}
    </style>
</head>
<body>
<main class="folha">
    <header class="cab"><img src="{{ $logo }}" alt="CDA"><h1>Câmara dos Despachantes Aduaneiros de Moçambique</h1><p>Plataforma CDA Digital</p></header>
    <h2 class="titulo">Declaração de quitação de quotas</h2>
    <p class="texto">Para os devidos efeitos, declara-se que <strong>{{ $membro->name }}</strong>, titular do número de membro <strong>{{ $membro->carteira }}</strong>@if($membro->entidade), associado à entidade <strong>{{ $membro->entidade }}</strong>@endif, se encontra com as quotas registadas na plataforma CDA Digital <strong>regularizadas até ao ano de {{ $ano }}</strong>.</p>
    <table class="resumo"><thead><tr><th>Ano</th><th>Estado</th><th>Valor</th><th>Pagamento</th></tr></thead><tbody>@foreach($quotas as $quota)<tr><td>{{ $quota->ano }}</td><td>{{ $quota->estado === 'paga' ? 'Paga' : 'Isenta' }}</td><td>{{ number_format($quota->valor, 0, ',', '.') }} MT</td><td>{{ $quota->data_pagamento?->format('d/m/Y') ?: '—' }}</td></tr>@endforeach</tbody></table>
    <p class="texto">A presente declaração é emitida electronicamente em {{ now()->format('d/m/Y') }} e a sua validade pode ser confirmada, em tempo real, através do código QR.</p>
    <section class="validacao"><img src="{{ $qrCode }}" alt="QR de verificação"><p><strong>Verificação de regularidade</strong><br>Leia o QR com um telemóvel ou leitor QR. A página de validação mostra sempre o estado financeiro actual.</p><p>Código: <strong>{{ $codigo }}</strong></p><div class="limpar"></div></section>
    <p class="assinatura"><span>CDA Digital</span></p>
</main>
@unless($pdf)
<div class="accoes"><button type="button" onclick="window.print()">Imprimir</button><a href="{{ route('membro.declaracao.descarregar') }}">Descarregar PDF</a></div>
@endunless
</body>
</html>
