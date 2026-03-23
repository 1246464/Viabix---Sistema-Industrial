<?php
require_once 'auth.php';

// Apenas admin pode acessar esta página
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}

$usuario = getUsuario();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Sistema de Projetos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f0f8f0;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #2e7d32;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1b5e20;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #666;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #555;
        }
        
        .btn-success {
            background: #4caf50;
            color: white;
        }
        
        .btn-danger {
            background: #f44336;
            color: white;
        }
        
        .btn-warning {
            background: #ff9800;
            color: white;
        }
        
        .btn-info {
            background: #2196f3;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .toolbar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .users-table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #2e7d32;
            color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
        }
        
        tbody tr {
            border-bottom: 1px solid #e0e0e0;
        }
        
        tbody tr:hover {
            background: #f5f5f5;
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-admin {
            background: #f44336;
            color: white;
        }
        
        .badge-lider {
            background: #2196f3;
            color: white;
        }
        
        .badge-visualizador {
            background: #4caf50;
            color: white;
        }
        
        .badge-ativo {
            background: #4caf50;
            color: white;
        }
        
        .badge-inativo {
            background: #9e9e9e;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .modal-header h2 {
            color: #2e7d32;
            font-size: 1.5rem;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2e7d32;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .info-box h3 {
            color: #1976d2;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .credentials {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 6px;
            font-family: monospace;
            margin-top: 8px;
        }
        
        .credentials div {
            margin: 5px 0;
        }
        
        .copy-btn {
            background: #2196f3;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .actions-cell {
            display: flex;
            gap: 5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>
                <i class="fas fa-users-cog"></i>
                Gerenciar Usuários
            </h1>
            <div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar ao Sistema
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="toolbar">
            <div>
                <h3><i class="fas fa-users"></i> Lista de Usuários</h3>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="fas fa-user-plus"></i> Novo Usuário
            </button>
        </div>
        
        <div class="users-table-container">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Nome</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p>Carregando usuários...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Modal Criar/Editar -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-user-plus"></i> Novo Usuário</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Usuário (Login) *
                    </label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-id-card"></i> Nome Completo *
                    </label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">
                        <i class="fas fa-lock"></i> Senha <span id="senhaOptional" style="display:none;">(deixe em branco para manter)</span>
                    </label>
                    <input type="text" id="senha" name="senha" required>
                </div>
                
                <div class="form-group">
                    <label for="nivel">
                        <i class="fas fa-shield-alt"></i> Nível de Acesso *
                    </label>
                    <select id="nivel" name="nivel" required>
                        <option value="visualizador">Visualizador (Apenas leitura)</option>
                        <option value="lider">Líder (Criar/Editar projetos)</option>
                        <option value="admin">Administrador (Acesso total)</option>
                    </select>
                </div>
                
                <div id="credentialsBox" class="info-box" style="display:none;">
                    <h3><i class="fas fa-info-circle"></i> Credenciais Criadas - Copie e envie para o usuário:</h3>
                    <div class="credentials" id="credentialsData"></div>
                    <button type="button" class="copy-btn" onclick="copyCredentials()">
                        <i class="fas fa-copy"></i> Copiar Credenciais
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let usuarios = [];
        let currentUserId = <?php echo $_SESSION['user_id']; ?>;
        let editingUserId = null;
        
        // Carregar usuários ao iniciar
        window.addEventListener('DOMContentLoaded', loadUsers);
        
        function loadUsers() {
            fetch('api_usuarios.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        usuarios = data.usuarios;
                        renderTable();
                    } else {
                        alert('Erro ao carregar usuários: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar usuários!');
                });
        }
        
        function renderTable() {
            const tbody = document.getElementById('usersTableBody');
            
            if (usuarios.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-users"></i>
                            <p>Nenhum usuário cadastrado</p>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = usuarios.map(user => {
                const nivelBadge = user.nivel === 'admin' ? 'badge-admin' : 
                                  user.nivel === 'lider' ? 'badge-lider' : 'badge-visualizador';
                const nivelText = user.nivel === 'admin' ? 'Admin' : 
                                 user.nivel === 'lider' ? 'Líder' : 'Visualizador';
                const statusBadge = user.ativo == 1 ? 'badge-ativo' : 'badge-inativo';
                const statusText = user.ativo == 1 ? 'Ativo' : 'Inativo';
                const isCurrentUser = user.id == currentUserId;
                const date = new Date(user.created_at).toLocaleDateString('pt-BR');
                
                return `
                    <tr>
                        <td>${user.id}</td>
                        <td><strong>${user.username}</strong></td>
                        <td>${user.nome}</td>
                        <td><span class="badge ${nivelBadge}">${nivelText}</span></td>
                        <td><span class="badge ${statusBadge}">${statusText}</span></td>
                        <td>${date}</td>
                        <td class="actions-cell">
                            <button class="btn btn-info btn-sm" onclick="editUser(${user.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${!isCurrentUser ? `
                                <button class="btn ${user.ativo == 1 ? 'btn-warning' : 'btn-success'} btn-sm" 
                                        onclick="toggleStatus(${user.id}, ${user.ativo == 1 ? 0 : 1})" 
                                        title="${user.ativo == 1 ? 'Desativar' : 'Ativar'}">
                                    <i class="fas fa-${user.ativo == 1 ? 'ban' : 'check'}"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(${user.id})" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function openCreateModal() {
            editingUserId = null;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Novo Usuário';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('senha').required = true;
            document.getElementById('senhaOptional').style.display = 'none';
            document.getElementById('credentialsBox').style.display = 'none';
            document.getElementById('userModal').classList.add('show');
        }
        
        function editUser(id) {
            const user = usuarios.find(u => u.id == id);
            if (!user) return;
            
            editingUserId = id;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Editar Usuário';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.username;
            document.getElementById('nome').value = user.nome;
            document.getElementById('senha').value = '';
            document.getElementById('senha').required = false;
            document.getElementById('senhaOptional').style.display = 'inline';
            document.getElementById('nivel').value = user.nivel;
            document.getElementById('credentialsBox').style.display = 'none';
            document.getElementById('userModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('show');
        }
        
        function saveUser(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const action = editingUserId ? 'update' : 'create';
            formData.append('action', action);
            
            if (editingUserId) {
                formData.append('ativo', 1);
            }
            
            fetch('api_usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    
                    // Se criou novo usuário, mostrar credenciais
                    if (!editingUserId && data.usuario) {
                        showCredentials(data.usuario);
                    } else {
                        closeModal();
                    }
                    
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao salvar usuário!');
            });
        }
        
        function showCredentials(usuario) {
            const nivelTexto = usuario.nivel === 'admin' ? 'Administrador' : 
                              usuario.nivel === 'lider' ? 'Líder de Projetos' : 'Visualizador';
            
            const credenciaisHTML = `
                <div><strong>Nome:</strong> ${usuario.nome}</div>
                <div><strong>Usuário:</strong> ${usuario.username}</div>
                <div><strong>Senha:</strong> ${usuario.senha}</div>
                <div><strong>Nível:</strong> ${nivelTexto}</div>
                <div><strong>Link:</strong> http://localhost/cristiano/login.php</div>
            `;
            
            document.getElementById('credentialsData').innerHTML = credenciaisHTML;
            document.getElementById('credentialsBox').style.display = 'block';
            
            // Armazenar para copiar
            window.lastCredentials = `
CREDENCIAIS DE ACESSO - Sistema de Projetos

Nome: ${usuario.nome}
Usuário: ${usuario.username}
Senha: ${usuario.senha}
Nível: ${nivelTexto}

Acesse: http://localhost/cristiano/login.php
            `.trim();
        }
        
        function copyCredentials() {
            if (window.lastCredentials) {
                navigator.clipboard.writeText(window.lastCredentials).then(() => {
                    alert('Credenciais copiadas! Agora você pode colar e enviar para o usuário.');
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                    alert('Erro ao copiar. Selecione o texto manualmente.');
                });
            }
        }
        
        function toggleStatus(id, ativo) {
            const action = ativo == 1 ? 'ativar' : 'desativar';
            if (!confirm(`Deseja realmente ${action} este usuário?`)) return;
            
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', id);
            formData.append('ativo', ativo);
            
            fetch('api_usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao alterar status!');
            });
        }
        
        function deleteUser(id) {
            if (!confirm('Tem certeza que deseja EXCLUIR este usuário? Esta ação não pode ser desfeita!')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('api_usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    loadUsers();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir usuário!');
            });
        }
    </script>
</body>
</html>
