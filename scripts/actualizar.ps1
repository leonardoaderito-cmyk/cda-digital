# Leonardo (leonardoaderito-cmyk): actualiza o portal com um pacote novo (.zip), sem tocar no .env,
# na base de dados nem nos ficheiros carregados (nao vem nenhum deles no pacote).
# Abrir o PowerShell como Administrador, na pasta do projecto:
#   powershell -ExecutionPolicy Bypass -File scripts\actualizar.ps1 -Pacote C:\Users\...\cda-digital-vps-AAAA-MM-DD.zip
param(
    [Parameter(Mandatory = $true)][string]$Pacote,
    [string]$Php = 'php'
)
$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz
$phpExe = (Get-Command $Php).Source

function Artisan {
    & $phpExe artisan @args
    if ($LASTEXITCODE -ne 0) { throw "Falhou: php artisan $args" }
}

Write-Host '==> Copia de seguranca da base de dados'
Artisan cda:copia-seguranca --apenas-base

$temporaria = Join-Path $env:TEMP ('cda-digital-' + [guid]::NewGuid())
New-Item -ItemType Directory -Path $temporaria | Out-Null
Write-Host '==> A extrair o pacote'
# Leonardo (leonardoaderito-cmyk): tar do Windows em vez de Expand-Archive, que e lento e falhava a meio com este pacote
& tar -x -f $Pacote -C $temporaria
if ($LASTEXITCODE -ne 0) { throw 'Falhou a extraccao do pacote' }
$novo = Join-Path $temporaria 'cda-digital'

Artisan down --retry=60 --refresh=15
try {
    Write-Host '==> A copiar o codigo novo'
    # Dependencias e recursos compilados sao substituidos por inteiro; o resto e copiado por cima
    foreach ($p in 'vendor', 'public\build') {
        & robocopy (Join-Path $novo $p) (Join-Path $raiz $p) /MIR /NFL /NDL /NJH /NJS /NP | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "robocopy falhou em $p" }
    }
    & robocopy $novo $raiz /E /XD (Join-Path $novo 'vendor') (Join-Path $novo 'public\build') /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw 'robocopy falhou' }

    Write-Host '==> Base de dados e caches'
    Artisan migrate --force
    # Indice do Assistente CDA (le de novo o texto dos PDFs que mudaram)
    Artisan cda:assistente-indexar
    Artisan optimize:clear
    Artisan optimize
    foreach ($p in 'storage', 'bootstrap\cache') {
        & icacls (Join-Path $raiz $p) /grant 'IIS_IUSRS:(OI)(CI)M' /T /Q | Out-Null
    }
} finally {
    Artisan up
    # Leonardo (leonardoaderito-cmyk): alguns PDFs do acervo passam os 260 caracteres de caminho; o robocopy esvazia a pasta antes do Remove-Item
    $vazia = Join-Path $env:TEMP ('cda-vazia-' + [guid]::NewGuid())
    New-Item -ItemType Directory -Path $vazia | Out-Null
    & robocopy $vazia $temporaria /MIR /NFL /NDL /NJH /NJS /NP | Out-Null
    Remove-Item -Recurse -Force $temporaria, $vazia
}
Write-Host 'Portal actualizado.' -ForegroundColor Green
