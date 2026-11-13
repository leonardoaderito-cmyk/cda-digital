# Leonardo (leonardoaderito-cmyk): pacote de actualizacao da VPS so com o que mudou desde o ultimo pacote entregue.
#   powershell -ExecutionPolicy Bypass -File scripts\empacotar-actualizacao.ps1 -Base dist\cda-digital-vps-AAAA-MM-DD.zip
#
# -Base : o pacote completo (ou a pasta extraida) que esta instalado na VPS.
# -Novo : a pasta cda-digital da versao nova (por omissao dist\pacote-vps\cda-digital, criada por scripts\empacotar.ps1 -Tipo vps).
# Resultado: dist\cda-digital-vps-actualizacao-AAAA-MM-DD.zip com os ficheiros novos ou alterados e REMOVER.txt
# com os que deixaram de existir. Na VPS aplica-se com scripts\actualizar-parcial.ps1.
param(
    [Parameter(Mandatory = $true)][string]$Base,
    [string]$Novo = ''
)
$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz
$utf8 = New-Object System.Text.UTF8Encoding $false
$data = Get-Date -Format 'yyyy-MM-dd'
$dist = Join-Path $raiz 'dist'
$tar = Join-Path $env:SystemRoot 'System32\tar.exe'

if (-not $Novo) {
    $Novo = Join-Path $dist 'pacote-vps\cda-digital'
    if (-not (Test-Path $Novo)) {
        & powershell -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'empacotar.ps1') -Tipo vps
        if ($LASTEXITCODE -ne 0) { throw 'Falhou a preparacao da versao nova.' }
    }
}
$Novo = (Resolve-Path $Novo).Path

# Pacote base: zip extraido para uma pasta temporaria, ou pasta ja extraida
$temporaria = $null
if ($Base -like '*.zip') {
    $temporaria = Join-Path $env:TEMP ('cda-base-' + [guid]::NewGuid())
    New-Item -ItemType Directory -Path $temporaria | Out-Null
    Write-Host '==> A extrair o pacote base'
    & $tar -xf (Resolve-Path $Base).Path -C $temporaria
    if ($LASTEXITCODE -ne 0) { throw 'Nao foi possivel extrair o pacote base.' }
    $pastaBase = Join-Path $temporaria 'cda-digital'
} else {
    $pastaBase = (Resolve-Path $Base).Path
    if (Test-Path (Join-Path $pastaBase 'cda-digital\artisan')) { $pastaBase = Join-Path $pastaBase 'cda-digital' }
}
if (-not (Test-Path (Join-Path $pastaBase 'artisan'))) { throw "O pacote base nao tem a pasta cda-digital: $Base" }

function Listar([string]$pasta) {
    $tabela = @{}
    # Caminho completo (sem nomes curtos tipo LEONAR~1), para cortar o prefixo certo de cada ficheiro
    $n = (Get-Item -LiteralPath $pasta).FullName.TrimEnd('\').Length + 1
    Get-ChildItem -LiteralPath $pasta -Recurse -File -Force | ForEach-Object {
        $tabela[$_.FullName.Substring($n).Replace('\', '/')] = $_
    }
    return $tabela
}

try {
    Write-Host '==> A comparar as duas versoes'
    $antigos = Listar $pastaBase
    $novos = Listar $Novo

    $mudados = New-Object System.Collections.Generic.List[string]
    foreach ($caminho in $novos.Keys) {
        # legado/ so serve para encher uma base nova (instalacao completa); numa VPS ja instalada nao faz falta
        if ($caminho -match '^legado/') { continue }
        $f = $novos[$caminho]
        $a = $antigos[$caminho]
        if (-not $a -or $a.Length -ne $f.Length) { $mudados.Add($caminho); continue }
        if ((Get-FileHash -LiteralPath $a.FullName -Algorithm MD5).Hash -ne (Get-FileHash -LiteralPath $f.FullName -Algorithm MD5).Hash) { $mudados.Add($caminho) }
    }
    # Ficheiros que sairam do codigo. Os documentos (acervo/) nunca sao apagados: podem estar ligados a base de dados.
    $removidos = $antigos.Keys | Where-Object { -not $novos.ContainsKey($_) -and $_ -notmatch '^(acervo|legado)/' } | Sort-Object

    # O script que aplica a actualizacao vai sempre no pacote
    foreach ($s in 'scripts/actualizar-parcial.ps1') { if (-not $mudados.Contains($s)) { $mudados.Add($s) } }

    $saida = Join-Path $dist 'pacote-actualizacao'
    if (Test-Path $saida) { Remove-Item -Recurse -Force $saida }
    $app = Join-Path $saida 'cda-digital'
    New-Item -ItemType Directory -Force -Path $app | Out-Null
    foreach ($caminho in $mudados) {
        $alvo = Join-Path $app $caminho
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $alvo) | Out-Null
        Copy-Item -LiteralPath $novos[$caminho].FullName -Destination $alvo
    }
    $nomeBase = Split-Path -Leaf $Base
    [IO.File]::WriteAllText((Join-Path $app 'REMOVER.txt'), (($removidos | ForEach-Object { $_ }) -join "`n") + "`n", $utf8)
    [IO.File]::WriteAllText((Join-Path $app 'ACTUALIZACAO.txt'),
        "Actualizacao de $data sobre o pacote $nomeBase`nFicheiros novos ou alterados: $($mudados.Count)`nFicheiros a remover: $(@($removidos).Count)`n", $utf8)

    $zip = Join-Path $dist "cda-digital-vps-actualizacao-$data.zip"
    if (Test-Path $zip) { Remove-Item -Force $zip }
    Push-Location $saida
    try { & $tar -a -c -f $zip 'cda-digital'; if ($LASTEXITCODE -ne 0) { throw 'Falhou a criacao do zip.' } } finally { Pop-Location }
    Remove-Item -Recurse -Force $saida

    $mb = [math]::Round((Get-Item $zip).Length / 1MB, 1)
    Write-Host "   alterados: $($mudados.Count)   a remover: $(@($removidos).Count)"
    Write-Host "   pronto: $zip ($mb MB)" -ForegroundColor Green
} finally {
    if ($temporaria) { Remove-Item -Recurse -Force $temporaria -ErrorAction SilentlyContinue }
}
