# Script PowerShell para empacotar o projeto para entrega
# Execute: .\criar_pacote.ps1

Write-Host "=== Criando Pacote para Entrega ===" -ForegroundColor Green
Write-Host ""

# Nome do arquivo ZIP
$nomeZip = "sistema_gestao_projetos_v1.0.zip"
$caminhoZip = "..\$nomeZip"

# Arquivos e pastas a INCLUIR
$arquivosIncluir = @(
    "index.php",
    "login.php",
    "logout.php",
    "auth.php",
    "config.php",
    "api_mysql.php",
    "api_usuarios.php",
    "usuarios_manager.php",
    "sse.php",
    "database.sql",
    "usuarios.sql",
    "setup.php",
    "remover_bom.ps1"
)

# Verificar se todos os arquivos existem
Write-Host "Verificando arquivos..." -ForegroundColor Yellow
$arquivosNaoEncontrados = @()
foreach ($arquivo in $arquivosIncluir) {
    if (Test-Path $arquivo) {
        Write-Host "  OK: $arquivo" -ForegroundColor Gray
    } else {
        Write-Host "  FALTANDO: $arquivo" -ForegroundColor Red
        $arquivosNaoEncontrados += $arquivo
    }
}

if ($arquivosNaoEncontrados.Count -gt 0) {
    Write-Host ""
    Write-Host "ERRO: Alguns arquivos não foram encontrados!" -ForegroundColor Red
    Write-Host "Arquivos faltando: $($arquivosNaoEncontrados -join ', ')" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Criando arquivo ZIP..." -ForegroundColor Yellow

# Remover ZIP anterior se existir
if (Test-Path $caminhoZip) {
    Remove-Item $caminhoZip -Force
    Write-Host "  - ZIP anterior removido" -ForegroundColor Gray
}

# Criar ZIP
Write-Host "  - Copiando arquivos..." -ForegroundColor Gray
Compress-Archive -Path $arquivosIncluir -DestinationPath $caminhoZip -CompressionLevel Optimal

# Verificar resultado
if (Test-Path $caminhoZip) {
    $tamanho = (Get-Item $caminhoZip).Length / 1MB
    $totalArquivos = $arquivosIncluir.Count
    
    Write-Host ""
    Write-Host "=== SUCESSO! ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Arquivo criado: $caminhoZip" -ForegroundColor Cyan
    Write-Host "Tamanho: $([math]::Round($tamanho, 2)) MB" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Total de arquivos: $totalArquivos" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "PROXIMO PASSO:" -ForegroundColor Yellow
    Write-Host "  1. Abra a pasta pai e encontre: $nomeZip"
    Write-Host "  2. Envie ao cliente por email ou WeTransfer"
    Write-Host "  3. Inclua as instruções do arquivo ENTREGA_CLIENTE.md"
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "ERRO: Falha ao criar o arquivo ZIP!" -ForegroundColor Red
    exit 1
}
