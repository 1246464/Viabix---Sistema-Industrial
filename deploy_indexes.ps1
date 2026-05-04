# ======================================================
# VIABIX SAAS - Phase 1 Priority 4 Deploy Script
# Database Indexes Setup
# PowerShell version for Windows / DigitalOcean
# ======================================================

param(
    [string]$DropletIP = "",
    [string]$DBHost = "localhost",
    [string]$DBPort = "3306",
    [string]$DBUser = "root",
    [string]$DBPassword = "",
    [string]$DBName = "viabix_db",
    [switch]$Local = $false,
    [switch]$Verbose = $false
)

# ======================================================
# Colors & Formatting
# ======================================================

function Write-Info {
    param([string]$Message)
    Write-Host "ℹ️  $Message" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Warning {
    param([string]$Message)
    Write-Host "⚠️  $Message" -ForegroundColor Yellow
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

# ======================================================
# Header
# ======================================================

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  VIABIX DATABASE INDEXES - Deployment Script (PowerShell) ║" -ForegroundColor Cyan
Write-Host "║  Phase 1 Priority 4                                        ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ======================================================
# Helper: Execute Remote SSH Command
# ======================================================

function Invoke-RemoteCommand {
    param(
        [string]$Command,
        [string]$Description = ""
    )
    
    if ($Description) {
        Write-Info $Description
    }
    
    try {
        $result = ssh root@$DropletIP $Command 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Error-Custom "Erro ao executar comando remoto: $Command"
            Write-Error-Custom $result
            return $null
        }
        if ($Verbose) {
            Write-Host $result
        }
        return $result
    }
    catch {
        Write-Error-Custom "Erro de SSH: $_"
        return $null
    }
}

# ======================================================
# Helper: Execute Local MySQL Command
# ======================================================

function Invoke-MysqlCommand {
    param(
        [string]$Command,
        [string]$Description = ""
    )
    
    if ($Description) {
        Write-Info $Description
    }
    
    try {
        if ($DBPassword) {
            $result = mysql -h $DBHost -P $DBPort -u $DBUser -p$DBPassword -e $Command 2>&1
        } else {
            $result = mysql -h $DBHost -P $DBPort -u $DBUser -e $Command 2>&1
        }
        
        if ($LASTEXITCODE -ne 0) {
            Write-Error-Custom "Erro ao executar comando MySQL: $Command"
            Write-Error-Custom $result
            return $null
        }
        if ($Verbose) {
            Write-Host $result
        }
        return $result
    }
    catch {
        Write-Error-Custom "Erro de MySQL: $_"
        return $null
    }
}

# ======================================================
# Step 1: Determine Execution Mode
# ======================================================

Write-Host ""
Write-Info "Step 1/5: Determinando modo de execução..."

if ($DropletIP -and -not $Local) {
    $Mode = "remote"
    Write-Success "Modo: Remote Deploy (SSH para DigitalOcean)"
    Write-Host "   Host: root@$DropletIP" -ForegroundColor Gray
} else {
    $Mode = "local"
    Write-Success "Modo: Local Deploy"
    Write-Host "   Host: $DBHost:$DBPort" -ForegroundColor Gray
}

Write-Host ""

# ======================================================
# Step 2: Verify Connectivity
# ======================================================

Write-Info "Step 2/5: Verificando conexão..."

if ($Mode -eq "remote") {
    $testSSH = ssh root@$DropletIP "echo 'SSH OK'" 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Error-Custom "Não foi possível conectar via SSH!"
        Write-Error-Custom "Verifique: ssh root@$DropletIP"
        exit 1
    }
    Write-Success "SSH conectado com sucesso!"
} else {
    # Test local MySQL
    if ($DBPassword) {
        $testSQL = mysql -h $DBHost -P $DBPort -u $DBUser -p$DBPassword -e "SELECT 1" 2>&1
    } else {
        $testSQL = mysql -h $DBHost -P $DBPort -u $DBUser -e "SELECT 1" 2>&1
    }
    
    if ($LASTEXITCODE -ne 0) {
        Write-Error-Custom "Não foi possível conectar ao banco de dados!"
        Write-Error-Custom "Host: $DBHost:$DBPort"
        Write-Error-Custom "User: $DBUser"
        exit 1
    }
    Write-Success "MySQL conectado com sucesso!"
}

Write-Host ""

# ======================================================
# Step 3: Verify Database Exists
# ======================================================

Write-Info "Step 3/5: Verificando se banco de dados '$DBName' existe..."

if ($Mode -eq "remote") {
    $checkDB = Invoke-RemoteCommand "mysql -u $DBUser -p$DBPassword -e 'SHOW DATABASES LIKE `"$DBName`"' | grep -c $DBName" "Consultando banco de dados remoto..."
    $dbExists = [int]$checkDB -gt 0
} else {
    $checkDB = Invoke-MysqlCommand "SHOW DATABASES LIKE '$DBName'" "Consultando banco de dados local..."
    $dbExists = $null -ne $checkDB -and $checkDB -like "*$DBName*"
}

if (-not $dbExists) {
    Write-Error-Custom "Banco de dados '$DBName' não encontrado!"
    exit 1
}

Write-Success "Banco de dados '$DBName' encontrado!"

Write-Host ""

# ======================================================
# Step 4: Get SQL Script Path
# ======================================================

Write-Info "Step 4/5: Localizando arquivo SQL..."

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$SQLFile = Join-Path $ScriptDir "BD\phase1_add_tenant_indexes.sql"

if (-not (Test-Path $SQLFile)) {
    Write-Error-Custom "Arquivo SQL não encontrado: $SQLFile"
    exit 1
}

Write-Success "Arquivo SQL encontrado!"
Write-Host "   Arquivo: $SQLFile" -ForegroundColor Gray

Write-Host ""

# ======================================================
# Step 5: Execute SQL Script
# ======================================================

Write-Info "Step 5/5: Executando script SQL..."
Write-Host "   Este processo pode levar alguns minutos..." -ForegroundColor Gray

$startTime = Get-Date

if ($Mode -eq "remote") {
    # For remote, we need to copy the file first or pipe it
    Write-Info "Copiando arquivo SQL para servidor remoto..."
    $copyResult = scp $SQLFile root@$DropletIP`:/tmp/phase1_add_tenant_indexes.sql 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Error-Custom "Erro ao copiar arquivo: $copyResult"
        exit 1
    }
    
    Write-Info "Executando script remoto..."
    $execResult = Invoke-RemoteCommand "mysql -u $DBUser -p$DBPassword $DBName < /tmp/phase1_add_tenant_indexes.sql" "Executando SQL remoto..."
    
    # Clean up temp file
    Invoke-RemoteCommand "rm /tmp/phase1_add_tenant_indexes.sql" "Limpando arquivos temporários..." | Out-Null
} else {
    # For local execution
    Write-Info "Executando script local..."
    $execResult = & mysql -h $DBHost -P $DBPort -u $DBUser $(if($DBPassword) {"-p$DBPassword"}) $DBName < $SQLFile 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Error-Custom "Erro ao executar script SQL!"
        Write-Host $execResult
        exit 1
    }
}

$endTime = Get-Date
$duration = $endTime - $startTime

Write-Success "Script SQL executado com sucesso!"
Write-Host "   Duração: $($duration.TotalSeconds) segundos" -ForegroundColor Gray

Write-Host ""

# ======================================================
# Step 6: Verify Indices
# ======================================================

Write-Info "Verificando índices criados..."

if ($Mode -eq "remote") {
    $indexCount = Invoke-RemoteCommand "mysql -u $DBUser -p$DBPassword -e 'USE $DBName; SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = `"$DBName`" AND column_name = `"tenant_id`" AND seq_in_index = 1;' | tail -1"
} else {
    $indexCount = & mysql -h $DBHost -P $DBPort -u $DBUser $(if($DBPassword) {"-p$DBPassword"}) -e "USE $DBName; SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = '$DBName' AND column_name = 'tenant_id' AND seq_in_index = 1;" | Select-Object -Last 1
}

Write-Success "Resumo da Execução:"
Write-Host "   📊 Índices criados em tenant_id: $indexCount"
Write-Host "   ⏱️  Timestamp: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

Write-Host ""

# ======================================================
# Summary
# ======================================================

Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║ ✅ PHASE 1 PRIORITY 4 - CONCLUÍDO!                         ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green

Write-Host ""

Write-Host "📈 Impacto Esperado:" -ForegroundColor Cyan
Write-Host "   • Queries filtradas por tenant: 10x mais rápido"
Write-Host "   • Redução de locks em tabelas grandes"
Write-Host "   • Melhor uso de índices compostos"

Write-Host ""

Write-Host "📋 Próximas Etapas:" -ForegroundColor Cyan
Write-Host "   1. Testar aplicação normalmente"
Write-Host "   2. Monitorar performance no Sentry"
Write-Host "   3. Começar Priority 5 (Tenant Isolation Audit)"

Write-Host ""

Write-Success "Deployment concluído com sucesso!"

Write-Host ""
Write-Host "📚 Para mais informações, veja PHASE_1_INDEXES.md" -ForegroundColor Gray

# ======================================================
# Usage Examples
# ======================================================

if (-not $DropletIP) {
    Write-Host ""
    Write-Host "💡 Exemplos de uso:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Local execution:"
    Write-Host "   .\deploy_indexes.ps1" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Remote execution (DigitalOcean):"
    Write-Host "   .\deploy_indexes.ps1 -DropletIP 123.45.67.89" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Com password:"
    Write-Host "   .\deploy_indexes.ps1 -DBPassword 'sua_senha'" -ForegroundColor Yellow
    Write-Host ""
}
