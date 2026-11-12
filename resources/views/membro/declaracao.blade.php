<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado de quitação {{ $codigo }}</title>
    <style>
        @page{margin:10mm}*{box-sizing:border-box}body{font-family:"DejaVu Sans",Arial,sans-serif;color:#171717;background:#fff;margin:0;padding:18px}.folha{position:relative;max-width:780px;margin:auto;background:#fff;border:2px solid #111;padding:10px}.moldura{border:1px solid #b5121b;padding:25px 36px 18px}.topo{width:100%;border-collapse:collapse;border-bottom:3px solid #111;padding-bottom:12px}.topo td{vertical-align:middle}.topo-logo{width:82px}.topo img{width:64px;height:64px;object-fit:contain}.instituicao{text-transform:uppercase;font-size:16px;font-weight:700;line-height:1.22}.subinstituicao{font-size:9px;color:#555;letter-spacing:.05em}.codigo{text-align:right;font-size:9px;color:#555}.selo{display:inline-block;margin-top:5px;padding:3px 8px;background:#1a7f37;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em}.titulo{text-align:center;margin:34px 0 6px;font-size:25px;text-transform:uppercase;letter-spacing:.1em}.ano{text-align:center;color:#b5121b;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.09em;margin-bottom:27px}.texto{font-size:12.5px;line-height:1.65;text-align:justify}.nome{font-size:15px;text-transform:uppercase}.dados{width:100%;border-collapse:collapse;margin:20px 0}.dados td{width:50%;border:1px solid #d4d4d4;padding:8px 11px;vertical-align:top}.dados span{display:block;color:#666;font-size:8px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}.dados strong{font-size:11px}.declaracao{margin:18px 0;padding:11px 14px;border-left:5px solid #b5121b;background:#f5f5f5;font-size:11px;line-height:1.55}.validacao{width:100%;border-collapse:collapse;margin-top:20px;border-top:1px solid #ccc}.validacao td{padding-top:12px;vertical-align:middle}.validacao-qr{width:112px}.validacao img{width:98px;height:98px}.validacao p{font-size:9px;color:#555;line-height:1.4;margin:2px 0}.validacao strong{color:#111}.endereco{font-size:7px!important;word-break:break-all}.assinatura{margin-top:27px;text-align:center}.assinatura span{display:inline-block;min-width:250px;border-top:1px solid #111;padding-top:6px;font-size:9px}.rodape{margin-top:15px;padding-top:7px;border-top:1px solid #ddd;color:#666;font-size:7px;text-align:center}.accoes{text-align:center;margin:22px}.accoes a,.accoes button{display:inline-block;background:#b5121b;color:#fff;border:0;padding:10px 18px;text-decoration:none;margin:0 4px;cursor:pointer;font-weight:700}.accoes button{background:#111}@media print{body{padding:0}.folha{border:2px solid #111}.accoes{display:none}}
    </style>
</head>
<body>
<main class="folha">
    <div class="moldura">
        <table class="topo" role="presentation"><tr>
            <td class="topo-logo"><img src="{{ $logo }}" alt="CDA"></td>
            <td><div class="instituicao">Câmara dos Despachantes<br>Aduaneiros de Moçambique</div><div class="subinstituicao">CDA DIGITAL &middot; DOCUMENTO ELECTRÓNICO VERIFICÁVEL</div></td>
            <td class="codigo">N.º {{ $codigo }}<br><span class="selo">Quotas regularizadas</span></td>
        </tr></table>

        <h1 class="titulo">Certificado de Quitação</h1>
        <p class="ano">Exercício de {{ $ano }}</p>

        <p class="texto">A <strong>Câmara dos Despachantes Aduaneiros de Moçambique</strong> certifica, para os devidos efeitos, que</p>
        <p class="texto nome"><strong>{{ $membro->name }}</strong></p>
        <p class="texto">titular do número de membro <strong>{{ $membro->carteira }}</strong>@if($entidade), associado à entidade <strong>{{ $entidade }}</strong>@endif, se encontra em situação financeira regular perante a CDA, com as quotas registadas <strong>integralmente regularizadas até {{ $ano }}</strong>.</p>

        <table class="dados" role="presentation"><tr>
            <td><span>Número de membro</span><strong>{{ $membro->carteira }}</strong></td>
            <td><span>Ano certificado</span><strong>{{ $ano }}</strong></td>
        </tr><tr>
            <td><span>Data de emissão</span><strong>{{ $emitidoEm->format('d/m/Y H:i') }}</strong></td>
            <td><span>Quotas abrangidas</span><strong>{{ $quotas->count() }} {{ $quotas->count() === 1 ? 'registo regularizado' : 'registos regularizados' }}</strong></td>
        </tr></table>

        <p class="declaracao">Este certificado é emitido automaticamente pela plataforma CDA Digital após confirmação da quitação. A sua autenticidade e a manutenção da regularidade podem ser verificadas a qualquer momento através do código QR.</p>

        <table class="validacao" role="presentation"><tr>
            <td class="validacao-qr"><img src="{{ $qrCode }}" alt="Código QR de verificação"></td>
            <td><p><strong>VERIFICAÇÃO ELECTRÓNICA</strong></p><p>Leia o código QR com um telemóvel. A página oficial confirma o membro, o ano, o código deste certificado e a situação financeira registada na CDA.</p><p>Código: <strong>{{ $codigo }}</strong></p><p class="endereco">{{ $urlVerificacao }}</p></td>
        </tr></table>

        <p class="assinatura"><span>Câmara dos Despachantes Aduaneiros de Moçambique<br><strong>Emissão electrónica CDA Digital</strong></span></p>
        <p class="rodape">Documento gerado electronicamente. Qualquer alteração invalida a correspondência com a verificação pública assinada.</p>
    </div>
</main>
@unless($pdf)
<div class="accoes"><button type="button" onclick="window.print()">Imprimir certificado</button><a href="{{ route('membro.declaracao.descarregar') }}">Descarregar PDF</a></div>
@endunless
</body>
</html>
