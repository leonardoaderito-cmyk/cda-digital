# Leonardo (leonardoaderito-cmyk): aplica na VPS um pacote de actualizacao parcial (so os ficheiros que mudaram).
# Nao toca no .env, na base de dados nem nos ficheiros carregados. Abrir o PowerShell como Administrador, na pasta do projecto:
#   powershell -ExecutionPolicy Bypass -File scripts\actualizar-parcial.ps1 -Pacote C:\Users\...\cda-digital-vps-actualizacao-AAAA-MM-DD.zip
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
Expand-Archive -Path $Pacote -DestinationPath $temporaria
$novo = Join-Path $temporaria 'cda-digital'
if (-not (Test-Path (Join-Path $novo 'ACTUALIZACAO.txt'))) { throw 'Este zip nao e um pacote de actualizacao parcial. Use scripts\actualizar.ps1 para o pacote completo.' }
Get-Content (Join-Path $novo 'ACTUALIZACAO.txt') | ForEach-Object { Write-Host "   $_" }

Artisan down --retry=60 --refresh=15
try {
    Write-Host '==> A copiar os ficheiros novos e alterados'
    & robocopy $novo $raiz /E /XF REMOVER.txt ACTUALIZACAO.txt /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw 'robocopy falhou' }

    Write-Host '==> A remover ficheiros que sairam do codigo'
    $lista = Join-Path $novo 'REMOVER.txt'
    if (Test-Path $lista) {
        foreach ($linha in Get-Content $lista) {
            $caminho = $linha.Trim()
            # So caminhos relativos dentro do projecto, e nunca dados do servidor
            if (-not $caminho -or $caminho -match '\.\.|^[\\/]|:' -or $caminho -match '^(acervo|storage|database/database\.sqlite|\.env)') { continue }
            $alvo = Join-Path $raiz ($caminho -replace '/', '\')
            if (Test-Path -LiteralPath $alvo -PathType Leaf) { Remove-Item -LiteralPath $alvo -Force }
        }
    }

    Write-Host '==> Base de dados e caches'
    Artisan migrate --force
    Artisan cda:assistente-indexar
    Artisan optimize:clear
    Artisan optimize
    foreach ($p in 'storage', 'bootstrap\cache') {
        & icacls (Join-Path $raiz $p) /grant 'IIS_IUSRS:(OI)(CI)M' /T /Q | Out-Null
    }
} finally {
    Artisan up
    Remove-Item -Recurse -Force $temporaria -ErrorAction SilentlyContinue
}
Write-Host 'Actualizacao parcial concluida.' -ForegroundColor Green
