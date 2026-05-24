# Script para remover BOM de arquivos PHP
Write-Host "=== Removendo BOM dos arquivos PHP ===" -ForegroundColor Green

$arquivos = @(
    "api_mysql.php",
    "api_usuarios.php", 
    "sse.php",
    "config.php",
    "auth.php",
    "index.php",
    "login.php"
)

foreach ($arquivo in $arquivos) {
    if (Test-Path $arquivo) {
        Write-Host "Processando: $arquivo" -ForegroundColor Yellow
        
        # Ler como bytes
        $bytes = [System.IO.File]::ReadAllBytes($arquivo)
        
        # Verificar se tem BOM (EF BB BF)
        if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
            Write-Host "  BOM encontrado! Removendo..." -ForegroundColor Red
            
            # Remover os 3 primeiros bytes (BOM)
            $semBOM = $bytes[3..($bytes.Length - 1)]
            
            # Salvar sem BOM
            [System.IO.File]::WriteAllBytes($arquivo, $semBOM)
            
            Write-Host "  BOM removido com sucesso!" -ForegroundColor Green
        } else {
            Write-Host "  Sem BOM. OK!" -ForegroundColor Gray
        }
    }
}

Write-Host ""
Write-Host "=== CONCLUÍDO ===" -ForegroundColor Green
Write-Host "Teste agora o sistema!"
