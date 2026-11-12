<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovativo {{ $codigo }}</title>
    <style>
        @page{margin:22mm}body{font-family:"DejaVu Sans",Arial,sans-serif;color:#18181b;background:#f4f4f5;margin:0;padding:32px}.folha{max-width:760px;margin:auto;background:#fff;padding:48px;border:1px solid #d4d4d8}.cab{border-bottom:3px solid #172554;padding-bottom:24px}.cab img{width:76px;height:76px;object-fit:cover;float:left;margin-right:18px}.cab h1{font-size:22px;margin:8px 0 6px}.cab p{margin:0;color:#52525b}.selo{float:right;color:#166534;font-weight:700;text-transform:uppercase;letter-spacing:.08em}.limpar{clear:both}.dados{margin:34px 0;width:100%;border-collapse:collapse}.dados td{width:50%;border-bottom:1px solid #e4e4e7;padding:12px 16px 12px 0;vertical-align:top}.dados span{display:block;color:#71717a;font-size:11px;text-transform:uppercase;margin-bottom:6px}.valor{font-size:28px}.validacao{border-top:1px solid #d4d4d8;padding-top:20px}.validacao img{width:118px;height:118px;float:right;margin-left:24px}.validacao p{color:#52525b;font-size:12px}.rodape{clear:both;padding-top:14px;color:#71717a;font-size:11px}.accoes{text-align:center;margin:22px}.accoes a,.accoes button{display:inline-block;background:#172554;color:white;border:0;border-radius:5px;padding:10px 18px;cursor:pointer;text-decoration:none;margin:0 4px}@media print{body{background:#fff;padding:0}.folha{border:0;padding:0}.accoes{display:none}}
    </style>
</head>
<body>
<main class="folha">
    <header class="cab"><span class="selo">Pago</span><img src="{{ $logo }}" alt="CDA"><h1>Câmara dos Despachantes Aduaneiros</h1><p>Comprovativo de pagamento de quota</p><div class="limpar"></div></header>
    <table class="dados" role="presentation">
        <tr><td><span>Membro</span><strong>{{ $quota->user->name }}</strong></td><td><span>Número de membro</span><strong>{{ $quota->user->carteira }}</strong></td></tr>
        <tr><td><span>Quota referente ao ano</span><strong>{{ $quota->ano }}</strong></td><td><span>Data do pagamento</span><strong>{{ $quota->data_pagamento?->format('d/m/Y') }}</strong></td></tr>
        <tr><td><span>Valor pago</span><strong class="valor">{{ number_format($quota->valor, 0, ',', '.') }} MT</strong></td><td><span>Referência</span><strong>{{ $quota->referencia ?: '—' }}</strong></td></tr>
    </table>
    <section class="validacao"><img src="{{ $qrCode }}" alt="QR de verificação"><p><strong>Validação electrónica</strong><br>Leia o QR para consultar a regularidade financeira actual deste membro directamente na plataforma CDA Digital.</p><p>Código: <strong>{{ $codigo }}</strong><br>Emitido em {{ now()->format('d/m/Y H:i') }}</p><div class="limpar"></div></section>
    <p class="rodape">Documento emitido electronicamente pela Câmara dos Despachantes Aduaneiros de Moçambique.</p>
</main>
@unless($pdf)
<div class="accoes"><button type="button" onclick="window.print()">Imprimir</button><a href="{{ route('membro.comprovativo.descarregar', $quota) }}">Descarregar PDF</a></div>
@endunless
</body>
</html>
