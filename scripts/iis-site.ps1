# Leonardo (leonardoaderito-cmyk): cria o site cda.bhub360.app no IIS, apontado para a pasta public.
# O Cloudflare esta em modo Full (strict): o site precisa de HTTPS com o Origin Certificate *.bhub360.app,
# que ja deve estar no servidor (LocalMachine\My) por causa dos outros subdominios.
# Abrir o PowerShell como Administrador e correr:
#   powershell -ExecutionPolicy Bypass -File scripts\iis-site.ps1
param(
    [string]$Dominio = 'cda.bhub360.app',
    [string]$Pool = 'cda-digital'
)
$ErrorActionPreference = 'Stop'
Import-Module WebAdministration
$publico = Join-Path (Split-Path -Parent $PSScriptRoot) 'public'

if (-not (Test-Path "IIS:\AppPools\$Pool")) {
    New-WebAppPool -Name $Pool | Out-Null
    # PHP nao usa .NET
    Set-ItemProperty "IIS:\AppPools\$Pool" -Name managedRuntimeVersion -Value ''
}

if (-not (Get-Website -Name $Dominio)) {
    New-Website -Name $Dominio -PhysicalPath $publico -ApplicationPool $Pool -HostHeader $Dominio -Port 80 | Out-Null
    Write-Host "Site $Dominio criado em $publico"
} else {
    Set-ItemProperty "IIS:\Sites\$Dominio" -Name physicalPath -Value $publico
    Write-Host "Site $Dominio ja existia; pasta actualizada para $publico"
}

# Certificado: o mais recente que cobre *.bhub360.app ou o proprio dominio
$zona = ($Dominio -split '\.', 2)[1]
$cert = Get-ChildItem Cert:\LocalMachine\My |
    Where-Object { $_.HasPrivateKey -and ($_.DnsNameList.Unicode -contains "*.$zona" -or $_.DnsNameList.Unicode -contains $Dominio) } |
    Sort-Object NotAfter -Descending | Select-Object -First 1

if (-not $cert) {
    Write-Warning "Nao encontrei certificado para *.$zona em LocalMachine\My."
    Write-Warning 'Cloudflare > SSL/TLS > Origin Server: crie o certificado, converta para .pfx, importe-o e corra este script de novo.'
    exit 1
}

if (-not (Get-WebBinding -Name $Dominio -Protocol https)) {
    # SslFlags 1 = SNI, para partilhar a porta 443 com os outros subdominios
    New-WebBinding -Name $Dominio -Protocol https -Port 443 -HostHeader $Dominio -SslFlags 1
}
(Get-WebBinding -Name $Dominio -Protocol https).AddSslCertificate($cert.Thumbprint, 'My')
Start-Website -Name $Dominio -ErrorAction SilentlyContinue

Write-Host "HTTPS ligado com o certificado $($cert.Subject) (valido ate $($cert.NotAfter.ToString('dd/MM/yyyy')))." -ForegroundColor Green
Write-Host "Teste: https://$Dominio/up"
