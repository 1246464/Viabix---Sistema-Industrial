<?php
/**
 * Script de teste para salvar ANVI
 * Acesse: http://localhost/fanavid/api/test_salvar.php
 */

require_once 'config.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    die('Você precisa estar logado primeiro. Faça login no sistema e tente novamente.');
}

$user_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Salvamento</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🧪 Teste de Salvamento de ANVI</h1>
    
    <div class="card">
        <h2>1. Criar ANVI de Teste</h2>
        <button onclick="criarANVITeste()">Criar ANVI de Teste</button>
        <div id="resultadoCriacao"></div>
    </div>
    
    <div class="card">
        <h2>2. Listar ANVIs no Banco</h2>
        <button onclick="listarANVIs()">Listar ANVIs</button>
        <div id="resultadoLista"></div>
    </div>
    
    <div class="card">
        <h2>3. Ver Estrutura da Tabela</h2>
        <?php
        try {
            $stmt = $pdo->query("DESCRIBE anvis");
            $columns = $stmt->fetchAll();
            
            echo "<table border='1' cellpadding='8'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p class='error'>Erro: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <script>
    async function criarANVITeste() {
        const resultado = document.getElementById('resultadoCriacao');
        resultado.innerHTML = 'Criando...';
        
        // Dados de teste
        const dadosTeste = {
            id: 'TESTE-001_01',
            numero: 'TESTE-001',
            revisao: '01',
            cliente: 'Cliente Teste',
            projeto: 'Projeto Teste',
            produto: 'Produto Teste',
            volumeMensal: 1000,
            dataANVI: new Date().toISOString().split('T')[0],
            status: 'em-andamento',
            informacoesBasicas: {
                anviNumber: 'TESTE-001',
                client: 'Cliente Teste',
                productDescription: 'Produto Teste'
            },
            tabelas: {
                materiaPrima: [
                    { col0: 'Vidro', col1: 'VF-04', col2: 'Vidro 4mm', col3: '7005.00.00', col4: '4', col5: 'm²', col6: '10', col7: '45.00', col8: '10', col9: '18' }
                ],
                insumos: [],
                componentes: [],
                processo: []
            }
        };
        
        try {
            const response = await fetch('anvi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(dadosTeste)
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultado.innerHTML = `<p class='success'>✅ ANVI de teste criada com sucesso! Versão: ${data.versao}</p>`;
            } else {
                resultado.innerHTML = `<p class='error'>❌ Erro: ${data.message || 'Desconhecido'}</p>`;
            }
        } catch (error) {
            resultado.innerHTML = `<p class='error'>❌ Erro: ${error.message}</p>`;
        }
    }
    
    async function listarANVIs() {
        const resultado = document.getElementById('resultadoLista');
        resultado.innerHTML = 'Carregando...';
        
        try {
            const response = await fetch('anvi.php', {
                credentials: 'include'
            });
            
            const data = await response.json();
            
            resultado.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } catch (error) {
            resultado.innerHTML = `<p class='error'>❌ Erro: ${error.message}</p>`;
        }
    }
    </script>
</body>
</html>