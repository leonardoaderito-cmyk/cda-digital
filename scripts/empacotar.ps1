# Leonardo (leonardoaderito-cmyk): prepara os pacotes de publicacao em dist\.
#   powershell -ExecutionPolicy Bypass -File scripts\empacotar.ps1              (os dois pacotes)
#   powershell -ExecutionPolicy Bypass -File scripts\empacotar.ps1 -Tipo vps
#   powershell -ExecutionPolicy Bypass -File scripts\empacotar.ps1 -Tipo partilhado
#
# vps        -> cda-digital-vps-AAAA-MM-DD.zip: pasta cda-digital\ para scripts\instalar.ps1 ou scripts\actualizar.ps1
#               (sem .env nem base de dados: sao criados no servidor).
# partilhado -> cda-digital-partilhado-AAAA-MM-DD.zip: cda-digital\ (fora da web) + public_html\ (pasta publica),
#               com .env e base de dados ja preparados, para alojamentos sem acesso a linha de comandos.
#
# Precisa de: git, php, composer e os recursos compilados (npm run build) no projecto.
param(
    [ValidateSet('ambos', 'vps', 'partilhado')][string]$Tipo = 'ambos',
    [string]$Php = 'php',
    [string]$Composer = ''
)
$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz
$phpExe = (Get-Command $Php).Source
$utf8 = New-Object System.Text.UTF8Encoding $false
# Composer: o indicado, o do PATH ou o composer.phar na pasta bin do utilizador
if (-not $Composer) {
    $c = Get-Command composer -ErrorAction SilentlyContinue
    $Composer = if ($c) { $c.Source } else { Join-Path $HOME 'bin\composer.phar' }
}
if (-not (Test-Path $Composer)) { throw "Composer nao encontrado. Indique-o com -Composer C:\caminho\composer.phar" }
[string[]]$composerArgs = if ($Composer -like '*.phar') { @($Composer) } else { @() }
$composerExe = if ($Composer -like '*.phar') { $phpExe } else { $Composer }
$data = Get-Date -Format 'yyyy-MM-dd'
$dist = Join-Path $raiz 'dist'
New-Item -ItemType Directory -Force -Path $dist | Out-Null

if (-not (Test-Path (Join-Path $raiz 'public\build\manifest.json'))) { throw 'Falta public\build: corra "npm run build" primeiro.' }
if (Test-Path (Join-Path $raiz 'public\hot')) { throw 'Existe public\hot (servidor Vite ligado): pare o "npm run dev" e apague o ficheiro.' }

function Correr([string]$pasta, [string]$exe, [string[]]$argumentos) {
    Push-Location $pasta
    try {
        & $exe @argumentos
        if ($LASTEXITCODE -ne 0) { throw "Falhou: $exe $argumentos" }
    } finally { Pop-Location }
}

# Copia o codigo (ficheiros do Git, incluindo os novos ainda sem commit) mais vendor\ e public\build\
function Preparar([string]$destino) {
    if (Test-Path $destino) { Remove-Item -Recurse -Force $destino }
    New-Item -ItemType Directory -Force -Path $destino | Out-Null

    # legado/docs/docs repete os PDFs que ja estao em acervo/ (237 MB); ia, tools e recolha sao ferramentas de recolha
    $fora = '^legado/(docs/docs|ia|tools|recolha)/|^(\.claude|\.github|\.cursor|\.vscode|tests|tmp|output|dist|node_modules)/|^(phpunit\.xml|start\.bat)$|^database/database\.sqlite'
    $ficheiros = (& git -c core.quotepath=off ls-files --cached --others --exclude-standard) | Where-Object { $_ -notmatch $fora }
    foreach ($f in $ficheiros) {
        $origem = Join-Path $raiz $f
        if (-not (Test-Path -LiteralPath $origem -PathType Leaf)) { continue }   # apagado mas ainda no indice
        $alvo = Join-Path $destino $f
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $alvo) | Out-Null
        Copy-Item -LiteralPath $origem -Destination $alvo
    }

    foreach ($p in 'vendor', 'public\build') {
        & robocopy (Join-Path $raiz $p) (Join-Path $destino $p) /E /NFL /NDL /NJH /NJS /NP | Out-Null
        if ($LASTEXITCODE -ge 8) { throw "robocopy falhou em $p" }
    }

    # Pastas de trabalho vazias que o Laravel e o portal esperam encontrar
    foreach ($p in 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'storage\logs',
                   'storage\app\private', 'bootstrap\cache', 'public\img\media', 'public\img\eventos', 'public\video\eventos', 'acervo\revistas') {
        New-Item -ItemType Directory -Force -Path (Join-Path $destino $p) | Out-Null
    }
    Get-ChildItem (Join-Path $destino 'bootstrap\cache') -Filter '*.php' | Remove-Item -Force
}

# Dependencias sem as ferramentas de desenvolvimento
function SemDev([string]$pasta) {
    Write-Host '   composer install --no-dev'
    Correr $pasta $composerExe (@($composerArgs) + @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress', '--quiet'))
}

function Zipar([string]$pasta, [string]$zip) {
    if (Test-Path $zip) { Remove-Item -Force $zip }
    # tar do Windows: caminhos com "/" dentro do zip, como esperam o Linux e o gestor de ficheiros do cPanel
    Correr $pasta 'tar' @('-a', '-c', '-f', $zip, '*')
    $mb = [math]::Round((Get-Item $zip).Length / 1MB)
    Write-Host "   pronto: $zip ($mb MB)" -ForegroundColor Green
}

if ($Tipo -in 'ambos', 'vps') {
    Write-Host '==> Pacote para a VPS (Windows Server / IIS)'
    $base = Join-Path $dist 'pacote-vps'
    if (Test-Path $base) { Remove-Item -Recurse -Force $base }
    $app = Join-Path $base 'cda-digital'
    Preparar $app
    SemDev $app
    Zipar $base (Join-Path $dist "cda-digital-vps-$data.zip")
}

if ($Tipo -in 'ambos', 'partilhado') {
    Write-Host '==> Pacote para alojamento partilhado (cPanel / Apache)'
    $base = Join-Path $dist 'pacote-partilhado'
    if (Test-Path $base) { Remove-Item -Recurse -Force $base }
    $app = Join-Path $base 'cda-digital'
    $web = Join-Path $base 'public_html'
    Preparar $app

    # A pasta publica passa para public_html, ao lado do projecto (que fica fora do alcance da web)
    Move-Item (Join-Path $app 'public') $web
    Remove-Item -Force (Join-Path $web 'web.config') -ErrorAction SilentlyContinue
    $indice = Join-Path $web 'index.php'
    $texto = [IO.File]::ReadAllText($indice) -replace "__DIR__\.'/\.\./", "__DIR__.'/../cda-digital/"
    [IO.File]::WriteAllText($indice, $texto, $utf8)
    [IO.File]::WriteAllText((Join-Path $app 'bootstrap\pasta-publica.php'),
        "<?php`n`n// Pasta publica do alojamento partilhado (public_html ao lado de cda-digital)`nreturn dirname(__DIR__, 2).'/public_html';`n", $utf8)
    # Se o projecto for parar dentro da pasta da web por engano, nada fica acessivel
    [IO.File]::WriteAllText((Join-Path $app '.htaccess'), "Require all denied`n", $utf8)

    # .env de producao: base SQLite em database\database.sqlite (caminho relativo ao projecto), copias em storage\app\copias
    $env = [IO.File]::ReadAllText((Join-Path $app '.env.production.example'))
    $env = $env -replace '(?m)^APP_URL=.*$', 'APP_URL=https://www.o-seu-dominio.co.mz'
    $env = $env -replace '(?m)^DB_DATABASE=.*\r?\n', ''
    $env = $env -replace '(?m)^CDA_COPIAS_PASTA=.*\r?\n', ''
    $env = $env -replace '(?m)^TRUSTED_PROXIES=.*$', 'TRUSTED_PROXIES='
    [IO.File]::WriteAllText((Join-Path $app '.env'), $env, $utf8)

    SemDev $app
    Write-Host '   chave, base de dados e importacao inicial'
    New-Item -ItemType File -Force -Path (Join-Path $app 'database\database.sqlite') | Out-Null
    Correr $app $phpExe @('artisan', 'key:generate', '--force', '--no-interaction')
    Correr $app $phpExe @('artisan', 'migrate', '--force', '--no-interaction')
    Correr $app $phpExe @('artisan', 'db:seed', '--force', '--no-interaction')
    # Conta inicial admin / admin: no primeiro acesso pede email e senha pessoais
    Correr $app $phpExe @('artisan', 'cda:admin-inicial', 'admin', 'admin', '--no-interaction')
    # Sem caches de configuracao: teriam os caminhos deste computador
    Correr $app $phpExe @('artisan', 'optimize:clear', '--no-interaction')
    Get-ChildItem (Join-Path $app 'bootstrap\cache') -Filter '*.php' | Remove-Item -Force
    Get-ChildItem (Join-Path $app 'storage\logs') -File | Remove-Item -Force
    Get-ChildItem (Join-Path $app 'storage\framework\views') -File | Remove-Item -Force

    Copy-Item (Join-Path $raiz 'docs\alojamento-partilhado.md') (Join-Path $base 'LEIA-ME.md')
    Zipar $base (Join-Path $dist "cda-digital-partilhado-$data.zip")
}
