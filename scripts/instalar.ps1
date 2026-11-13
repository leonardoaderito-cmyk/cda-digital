# Leonardo (leonardoaderito-cmyk): primeira instalacao no Windows Server a partir do pacote
# (dependencias PHP e recursos compilados ja incluidos: nao e preciso Composer nem Node).
# Abrir o PowerShell como Administrador, na pasta do projecto, e correr:
#   powershell -ExecutionPolicy Bypass -File scripts\instalar.ps1
# Opcoes: -Php C:\php\php.exe   -Copias D:\copias\cda-digital
#         -SemIIS (nao da permissoes ao IIS, p. ex. com Apache)   -SemTarefa (nao cria a tarefa agendada)
param(
    [string]$Php = 'php',
    [string]$Copias = 'C:\copias\cda-digital',
    [switch]$SemIIS,
    [switch]$SemTarefa
)
$ErrorActionPreference = 'Stop'
$raiz = Split-Path -Parent $PSScriptRoot
Set-Location $raiz
$phpExe = (Get-Command $Php).Source
$utf8 = New-Object System.Text.UTF8Encoding $false

function Artisan {
    & $phpExe artisan @args
    if ($LASTEXITCODE -ne 0) { throw "Falhou: php artisan $args" }
}

# 1. Ficheiro .env (sem BOM, para o Laravel o ler correctamente)
$envFicheiro = Join-Path $raiz '.env'
if (-not (Test-Path $envFicheiro)) {
    $base = (Join-Path $raiz 'database\database.sqlite') -replace '\\', '/'
    $texto = [IO.File]::ReadAllText((Join-Path $raiz '.env.production.example'))
    # Entre aspas: o caminho pode ter espacos
    $texto = $texto -replace '(?m)^DB_DATABASE=.*$', "DB_DATABASE=`"$base`""
    $texto = $texto -replace '(?m)^CDA_COPIAS_PASTA=.*$', ('CDA_COPIAS_PASTA="' + ($Copias -replace '\\', '/') + '"')
    [IO.File]::WriteAllText($envFicheiro, $texto, $utf8)
    Write-Host "Criado o ficheiro .env em $envFicheiro" -ForegroundColor Yellow
    Write-Host 'Preencha os dados de email (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD) e corra este script de novo.' -ForegroundColor Yellow
    exit 1
}

if (-not (Select-String -Path $envFicheiro -Pattern '^APP_KEY=base64:' -Quiet)) {
    Artisan key:generate --force
}

# 2. Pastas e base de dados
$linha = Select-String -Path $envFicheiro -Pattern '^DB_DATABASE=(.*)$' | Select-Object -First 1
$base = if ($linha) { $linha.Matches[0].Groups[1].Value.Trim('"') } else { Join-Path $raiz 'database\database.sqlite' }
$pastas = 'storage\framework\cache\data', 'storage\framework\sessions', 'storage\framework\views', 'storage\logs',
          'storage\app\private', 'bootstrap\cache', 'public\img\media', 'public\img\eventos', 'public\video\eventos', 'acervo\revistas'
foreach ($p in $pastas) { New-Item -ItemType Directory -Force -Path (Join-Path $raiz $p) | Out-Null }
New-Item -ItemType Directory -Force -Path $Copias | Out-Null

$nova = (-not (Test-Path $base)) -or ((Get-Item $base).Length -eq 0)
if (-not (Test-Path $base)) {
    New-Item -ItemType Directory -Force -Path (Split-Path -Parent $base) | Out-Null
    New-Item -ItemType File -Path $base | Out-Null
}

Artisan migrate --force
if ($nova) {
    # A importacao termina com o indice do Assistente CDA
    Write-Host '==> Base nova: a importar membros, noticias, paginas e documentos'
    Artisan db:seed --force
} else {
    Artisan cda:assistente-indexar
}
Artisan optimize:clear
Artisan optimize

# 3. Permissoes de escrita para o IIS (grupo IIS_IUSRS inclui a identidade do application pool)
if (-not $SemIIS) {
    $escrita = 'storage', 'bootstrap\cache', 'database', 'acervo', 'public\img\media', 'public\img\eventos', 'public\video\eventos', 'publicideo\eventos'
    foreach ($p in $escrita) {
        & icacls (Join-Path $raiz $p) /grant 'IIS_IUSRS:(OI)(CI)M' /T /Q | Out-Null
    }
    if ((Split-Path -Parent $base) -notlike "$raiz*") {
        & icacls (Split-Path -Parent $base) /grant 'IIS_IUSRS:(OI)(CI)M' /T /Q | Out-Null
    }
}

# 4. Copia de seguranca diaria as 02:00 (Agendador de Tarefas do Windows, conta SYSTEM)
if (-not $SemTarefa) {
    $accao = New-ScheduledTaskAction -Execute $phpExe -Argument 'artisan cda:copia-seguranca' -WorkingDirectory $raiz
    $gatilho = New-ScheduledTaskTrigger -Daily -At '02:00'
    Register-ScheduledTask -TaskName 'CDA Digital - copia de seguranca' -Action $accao -Trigger $gatilho `
        -User 'SYSTEM' -RunLevel Highest -Force | Out-Null
    Write-Host "Tarefa agendada criada: copia de seguranca diaria as 02:00 em $Copias"
}

Write-Host ''
Write-Host 'Instalacao concluida.' -ForegroundColor Green
Write-Host 'Proximos passos:'
Write-Host "  1. Criar o primeiro administrador:  php artisan cda:admin <email>"
Write-Host '  2. Criar o site no IIS:             powershell -ExecutionPolicy Bypass -File scripts\iis-site.ps1'
