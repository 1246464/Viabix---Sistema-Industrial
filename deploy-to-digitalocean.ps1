# UPLOAD & DEPLOY SCRIPT - VIABIX Auth V2.0 to DigitalOcean
# PowerShell Script for Windows Users
# Usage: .\deploy-to-digitalocean.ps1 -DropletIP "146.190.244.133" -SSHKey "C:\path\to\id_rsa"

param(
    [Parameter(Mandatory=$true)]
    [string]$DropletIP,
    
    [Parameter(Mandatory=$false)]
    [string]$SSHKey = "$HOME\.ssh\id_rsa",
    
    [Parameter(Mandatory=$false)]
    [string]$SSHUser = "root",
    
    [Parameter(Mandatory=$false)]
    [string]$AppDir = "/var/www/html"
)

# Colors
$colors = @{
    Green = "DarkGreen"
    Red = "Red"
    Yellow = "Yellow"
    Blue = "Cyan"
}

function Write-Color {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    switch ($Level) {
        "SUCCESS" { Write-Color "[$timestamp] ✓ $Message" $colors.Green }
        "ERROR" { Write-Color "[$timestamp] ✗ $Message" $colors.Red }
        "WARNING" { Write-Color "[$timestamp] ⚠ $Message" $colors.Yellow }
        "INFO" { Write-Color "[$timestamp] ℹ $Message" $colors.Blue }
    }
}

# ============================================================
# VALIDAR PRÉ-REQUISITOS
# ============================================================
Write-Host ""
Write-Color "╔════════════════════════════════════════════════════════════════╗" $colors.Blue
Write-Color "║    UPLOAD & DEPLOY - VIABIX Auth V2.0 to DigitalOcean         ║" $colors.Blue
Write-Color "╚════════════════════════════════════════════════════════════════╝" $colors.Blue
Write-Host ""

Log "Validando pré-requisitos..." INFO

# Check SSH Key
if (-not (Test-Path $SSHKey)) {
    Log "Chave SSH não encontrada: $SSHKey" ERROR
    exit 1
}
Log "Chave SSH encontrada: $SSHKey" SUCCESS

# Check if running from project root
if (-not (Test-Path ".\api\auth_system.php")) {
    Log "Execute este script do diretório raiz do projeto (c:\xampp\htdocs\ANVI)" ERROR
    exit 1
}
Log "Diretório do projeto validado" SUCCESS

# Test SSH connection
Log "Testando conexão SSH com $SSHUser@$DropletIP..." INFO
try {
    $testCmd = "ssh -i `"$SSHKey`" -o ConnectTimeout=5 $SSHUser@$DropletIP 'echo OK'"
    $result = Invoke-Expression $testCmd 2>&1
    if ($result -like "*OK*") {
        Log "Conexão SSH OK" SUCCESS
    } else {
        Log "Erro ao conectar via SSH: $result" ERROR
        exit 1
    }
} catch {
    Log "Erro na conexão SSH: $_" ERROR
    exit 1
}

# ============================================================
# CRIAR LISTA DE ARQUIVOS PARA UPLOAD
# ============================================================
Write-Host ""
Log "Preparando arquivos para upload..." INFO

$filesToUpload = @(
    # Novos arquivos
    "api\auth_system.php",
    "api\permissions.php",
    "api\tests\AuthSystemTest.php",
    "BD\migracao_permissoes.sql",
    
    # Modificados
    "api\config.php",
    
    # Documentação
    "SUMARIO_ENTREGA_AUTH.md",
    "IMPLEMENTACAO_AUTH_V2.md",
    "GUIA_TESTE_AUTH.md",
    
    # Scripts
    "deploy-auth-v2-digitalocean.sh",
    "validate_auth_system.sh",
    
    # Essencial para deploy
    ".env.production",
    "composer.json"
)

$missingFiles = @()
foreach ($file in $filesToUpload) {
    if (-not (Test-Path $file)) {
        $missingFiles += $file
    }
}

if ($missingFiles.Count -gt 0) {
    Write-Color "AVISO: Os seguintes arquivos não encontrados (serão ignorados):" $colors.Yellow
    foreach ($file in $missingFiles) {
        Write-Color "  - $file" $colors.Yellow
    }
    Write-Host ""
}

# Filter only existing files
$filesToUpload = $filesToUpload | Where-Object { Test-Path $_ }

Log "Total de arquivos para upload: $($filesToUpload.Count)" SUCCESS
foreach ($file in $filesToUpload) {
    Write-Host "  + $file"
}

# ============================================================
# CRIAR ARQUIVO COMPACTADO
# ============================================================
Write-Host ""
Log "Compactando arquivos..." INFO

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$zipFile = "viabix-auth-v2-$timestamp.zip"

# Remove if exists
if (Test-Path $zipFile) {
    Remove-Item $zipFile -Force
}

# Create zip
$filesToZip = @()
foreach ($file in $filesToUpload) {
    $filesToZip += (Get-Item $file).FullName
}

Compress-Archive -Path $filesToZip -DestinationPath $zipFile -Force

$zipSize = (Get-Item $zipFile).Length / 1MB
Log "Arquivo compactado: $zipFile ($([math]::Round($zipSize, 2)) MB)" SUCCESS

# ============================================================
# UPLOAD VIA SCP
# ============================================================
Write-Host ""
Log "Iniciando upload para DigitalOcean..." INFO

$remoteDir = "/tmp/viabix-deploy"
$scpCmd = "scp -i `"$SSHKey`" -r `"$zipFile`" $SSHUser@$DropletIP`:$remoteDir/"

try {
    Write-Host "Executando: $scpCmd"
    & cmd /c $scpCmd
    Log "Upload concluído" SUCCESS
} catch {
    Log "Erro no upload: $_" ERROR
    exit 1
}

# ============================================================
# EXTRAIR E DEPLOY NO SERVIDOR
# ============================================================
Write-Host ""
Log "Extraindo e implantando no servidor..." INFO

$deployScript = @"
#!/bin/bash
set -e

echo "Extraindo arquivos..."
cd /tmp/viabix-deploy
unzip -q $zipFile -d extract

echo "Copiando arquivos para $AppDir..."
cp -v extract/api/auth_system.php $AppDir/api/
cp -v extract/api/permissions.php $AppDir/api/
cp -v extract/api/config.php $AppDir/api/
cp -vr extract/api/tests $AppDir/api/ 2>/dev/null || true
cp -v extract/BD/migracao_permissoes.sql $AppDir/BD/
cp -v extract/*.md $AppDir/ 2>/dev/null || true
cp -v extract/*.sh $AppDir/ 2>/dev/null || true

echo "Aplicando permissões..."
chown -R www-data:www-data $AppDir
chmod -R 755 $AppDir
chmod 644 $AppDir/api/*.php

echo "Limpando arquivos temporários..."
rm -rf /tmp/viabix-deploy

echo "✓ Deploy concluído com sucesso!"
echo ""
echo "Próximos passos:"
echo "1. Executar migração SQL: mysql -h \$DB_HOST -u \$DB_USER -p\$DB_PASS \$DB_NAME < $AppDir/BD/migracao_permissoes.sql"
echo "2. Testar endpoint: curl -X GET http://seu_dominio/ANVI/api/permissions.php"
echo ""
"@

$deployFile = "deploy-temp-$timestamp.sh"
$deployScript | Out-File -FilePath $deployFile -Encoding UTF8

try {
    # Upload script
    $uploadCmd = "scp -i `"$SSHKey`" `"$deployFile`" $SSHUser@$DropletIP`:/tmp/"
    & cmd /c $uploadCmd
    
    # Execute on server
    $execCmd = "ssh -i `"$SSHKey`" $SSHUser@$DropletIP 'bash /tmp/$deployFile'"
    & cmd /c $execCmd
    
    Log "Deploy executado no servidor" SUCCESS
} catch {
    Log "Erro durante deploy: $_" ERROR
    exit 1
}

# ============================================================
# VALIDAÇÃO FINAL
# ============================================================
Write-Host ""
Log "Validando implantação..." INFO

$validateScript = @"
echo "Verificando arquivos..."
test -f $AppDir/api/auth_system.php && echo "✓ auth_system.php" || echo "✗ auth_system.php"
test -f $AppDir/api/permissions.php && echo "✓ permissions.php" || echo "✗ permissions.php"
test -f $AppDir/api/config.php && echo "✓ config.php" || echo "✗ config.php"
test -f $AppDir/BD/migracao_permissoes.sql && echo "✓ migracao_permissoes.sql" || echo "✗ migracao_permissoes.sql"

echo ""
echo "Verificando sintaxe PHP..."
php -l $AppDir/api/auth_system.php && echo "✓ auth_system.php OK" || echo "✗ Erro de sintaxe"
php -l $AppDir/api/permissions.php && echo "✓ permissions.php OK" || echo "✗ Erro de sintaxe"

echo ""
echo "Proximas etapas:"
echo "1. Executar migração SQL manualmente"
echo "2. Testar endpoint /api/permissions"
echo "3. Rodar testes: vendor/bin/phpunit api/tests/AuthSystemTest.php"
"@

$validateScript | Out-File -FilePath "validate-temp-$timestamp.sh" -Encoding UTF8

try {
    $uploadCmd = "scp -i `"$SSHKey`" `"validate-temp-$timestamp.sh`" $SSHUser@$DropletIP`:/tmp/"
    & cmd /c $uploadCmd
    
    Log "Executando validação..." INFO
    $execCmd = "ssh -i `"$SSHKey`" $SSHUser@$DropletIP 'bash /tmp/validate-temp-$timestamp.sh'"
    & cmd /c $execCmd
    
} catch {
    Log "Aviso: Erro na validação" WARNING
}

# ============================================================
# CLEANUP
# ============================================================
Write-Host ""
Log "Limpando arquivos locais..." INFO

Remove-Item $zipFile -Force
Remove-Item $deployFile -Force
Remove-Item "validate-temp-$timestamp.sh" -Force

Log "Arquivos temporários removidos" SUCCESS

# ============================================================
# RESULTADO FINAL
# ============================================================
Write-Host ""
Write-Color "╔════════════════════════════════════════════════════════════════╗" $colors.Blue
Write-Color "║              ✓ UPLOAD & DEPLOY CONCLUÍDO!                     ║" $colors.Blue
Write-Color "╚════════════════════════════════════════════════════════════════╝" $colors.Blue
Write-Host ""

Log "Servidor: $SSHUser@$DropletIP" INFO
Log "App Dir: $AppDir" INFO
Log "Timestamp: $timestamp" INFO

Write-Host ""
Write-Color "PRÓXIMOS PASSOS:" $colors.Yellow
Write-Host "1. Conectar ao servidor:"
Write-Host "   ssh $SSHUser@$DropletIP"
Write-Host ""
Write-Host "2. Executar migração SQL:"
Write-Host "   cd $AppDir"
Write-Host "   mysql -h \$DB_HOST -u \$DB_USER -p\$DB_PASS \$DB_NAME < BD/migracao_permissoes.sql"
Write-Host ""
Write-Host "3. Testar endpoint:"
Write-Host "   curl -X GET http://seu_dominio/ANVI/api/permissions.php -H 'Cookie: PHPSESSID=...'"
Write-Host ""
Write-Host "4. Verificar logs:"
Write-Host "   tail -f $AppDir/logs/error.log"
Write-Host ""
Write-Color "✓ Sucesso!" $colors.Green
Write-Host ""
