<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('titulo') &middot; CDA</title>
    {{-- Autor: Leonardo (leonardoaderito-cmyk) - folha oficial da CDA para declaracoes, comprovativos e extractos (ecra, impressao e PDF via dompdf).
         So tabelas e blocos simples: o dompdf nao suporta flex nem grid. --}}
    <style>
        @page { margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; font-size: 11px; line-height: 1.5; color: #1a1a1a; background: #ffffff; }
        body.ecra { background: #e5e5e5; padding: 24px 12px; }
        .folha { position: relative; max-width: 794px; margin: 0 auto; background: #ffffff; border: 1px solid #b5121b; padding: 4px; }
        body.ecra .folha { box-shadow: 0 6px 24px rgba(0, 0, 0, .18); }
        .moldura { position: relative; border: 2px solid #111111; padding: 20px 30px 14px; }
        .marca-agua { position: absolute; top: 300px; left: 50%; width: 300px; height: 300px; margin-left: -150px; opacity: .05; }

        .topo { width: 100%; border-collapse: collapse; }
        .topo td { vertical-align: middle; padding: 0; }
        .topo-logo { width: 72px; }
        .topo-logo img { width: 60px; height: 60px; }
        .topo-nome strong { display: block; font-size: 13.5px; line-height: 1.25; text-transform: uppercase; letter-spacing: .02em; }
        .topo-nome span { display: block; margin-top: 3px; color: #555555; font-size: 8.5px; letter-spacing: .08em; text-transform: uppercase; }
        .topo-ref { width: 190px; text-align: right; }
        .topo-ref span { display: block; color: #555555; font-size: 8px; text-transform: uppercase; letter-spacing: .06em; }
        .topo-ref strong { display: inline-block; margin-top: 2px; padding: 3px 7px; border: 1px solid #111111; font-size: 9.5px; letter-spacing: .03em; }
        .faixa { height: 4px; margin: 12px 0 0; background: #b5121b; border-bottom: 1px solid #111111; }
        .faixa-fina { height: 1px; margin: 2px 0 0; background: #111111; }

        .titulo { margin: 26px 0 2px; text-align: center; font-size: 22px; letter-spacing: .12em; text-transform: uppercase; }
        .subtitulo { margin: 0 0 22px; text-align: center; color: #b5121b; font-size: 10.5px; font-weight: bold; letter-spacing: .1em; text-transform: uppercase; }
        .local-data { margin: 0 0 14px; text-align: right; font-size: 11px; }
        .texto { margin: 0 0 11px; font-size: 12px; line-height: 1.75; text-align: justify; }
        .texto strong { color: #000000; }

        .quadro { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .quadro td { width: 33.33%; padding: 7px 10px; border: 1px solid #cfcfcf; vertical-align: top; }
        .quadro span { display: block; margin-bottom: 2px; color: #666666; font-size: 7.5px; letter-spacing: .06em; text-transform: uppercase; }
        .quadro strong { font-size: 10.5px; }
        .quadro .verde { color: #1a7f37; }

        .seccao { margin: 16px 0 6px; font-size: 9px; letter-spacing: .08em; text-transform: uppercase; }
        .lista { width: 100%; border-collapse: collapse; font-size: 10px; }
        .lista th { padding: 5px 8px; background: #111111; color: #ffffff; font-size: 7.5px; font-weight: bold; letter-spacing: .06em; text-align: left; text-transform: uppercase; }
        .lista td { padding: 5px 8px; border-bottom: 1px solid #dddddd; }
        .lista tbody tr:nth-child(even) td { background: #f6f6f6; }
        .lista .num { text-align: right; white-space: nowrap; }
        .lista tfoot td { border-top: 2px solid #111111; border-bottom: 0; font-weight: bold; background: #ffffff; }
        .etiqueta { display: inline-block; padding: 1px 6px; color: #ffffff; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .etiqueta.paga { background: #1a7f37; } .etiqueta.isenta { background: #6e6e6e; } .etiqueta.pendente { background: #111111; } .etiqueta.em_atraso { background: #b5121b; }

        .fecho { width: 100%; border-collapse: collapse; margin-top: 22px; table-layout: fixed; }
        .fecho td { vertical-align: middle; padding: 0; }
        .fecho-qr { width: 100px; }
        .fecho-qr img { width: 92px; height: 92px; }
        .fecho-verificacao p { margin: 0 0 3px; padding-right: 10px; color: #555555; font-size: 8.5px; line-height: 1.45; }
        .fecho-verificacao strong { color: #111111; }
        .fecho-selo { width: 140px; text-align: center; }
        .fecho-selo img { width: 128px; height: 128px; }
        .fecho-assinatura { width: 165px; text-align: center; }
        .fecho-assinatura .linha { margin: 34px 8px 5px; border-top: 1px solid #111111; }
        .fecho-assinatura p { margin: 0; font-size: 9px; line-height: 1.4; }
        .fecho-assinatura small { color: #555555; font-size: 7.5px; }
        .endereco { font-size: 6.5px !important; word-break: break-all; }

        .rodape { margin-top: 16px; padding-top: 7px; border-top: 1px solid #cfcfcf; color: #666666; font-size: 7.5px; line-height: 1.5; text-align: center; }
        .accoes { margin: 20px auto 0; text-align: center; }
        .accoes a, .accoes button { display: inline-block; margin: 0 4px; padding: 10px 18px; border: 0; background: #b5121b; color: #ffffff; font: bold 13px Arial, sans-serif; text-decoration: none; cursor: pointer; }
        .accoes button { background: #111111; }
        .accoes a.voltar { background: #ffffff; color: #111111; border: 1px solid #111111; }
        @media (max-width: 640px) { .moldura { padding: 16px 14px 12px; } .titulo { font-size: 17px; } .topo-ref { display: none; } }
        @media print { body.ecra { background: #ffffff; padding: 0; } body.ecra .folha { box-shadow: none; } .accoes { display: none; } }
        @yield('estilos')
    </style>
</head>
<body class="{{ $pdf ? 'pdf' : 'ecra' }}">
<main class="folha">
    <div class="moldura">
        <img class="marca-agua" src="{{ $logo }}" alt="">
        <table class="topo" role="presentation"><tr>
            <td class="topo-logo"><img src="{{ $logo }}" alt="CDA"></td>
            <td class="topo-nome"><strong>Câmara dos Despachantes<br>Aduaneiros de Moçambique</strong><span>Secretaria-Geral &middot; Documento electrónico verificável</span></td>
            <td class="topo-ref"><span>@yield('referencia-rotulo', 'Referência')</span><strong>{{ $codigo }}</strong></td>
        </tr></table>
        <div class="faixa"></div>

        @yield('conteudo')

        <p class="rodape">Câmara dos Despachantes Aduaneiros de Moçambique &middot; Maputo, Moçambique &middot; +258 21 305 504<br>@yield('rodape', 'Documento emitido electronicamente pela plataforma CDA Digital. Qualquer alteração invalida a verificação pública.')</p>
    </div>
</main>
@unless ($pdf)
    <div class="accoes">@yield('accoes')</div>
@endunless
</body>
</html>
