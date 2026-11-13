<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficheiros demasiado grandes &lsaquo; CDA</title>
    @vite(['resources/css/app.css'])
</head>
<body class="admin antialiased">
    <main class="erro-pedido">
        <p class="erro-pedido-codigo">413</p>
        <h1>O conjunto de ficheiros é demasiado grande</h1>
        <p>O servidor aceita no máximo {{ $limite }} por envio. Reduza o tamanho ou envie as imagens em dois grupos.</p>
        <button type="button" class="admin-botao" onclick="history.back()">Voltar às imagens</button>
    </main>
</body>
</html>
