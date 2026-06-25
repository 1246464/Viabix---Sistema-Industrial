// =========================================
// SISTEMA DE AUTENTICAÇÃO E USUÁRIOS (MySQL via PHP)
// =========================================
let usuarioAtual = null;

function redirecionarParaLoginCentral() {
    const retorno = `${window.location.pathname.split('/').pop()}${window.location.search || ''}`;
    window.location.href = `login.html?redirect=${encodeURIComponent(retorno)}`;
}

// Verificar sessão ao carregar a página
async function verificarSessao() {
    try {
        const data = await ViabixApiCore.checkSession({ force: true, ttlMs: 0 });
        const autenticado = ViabixApiCore.isAuthenticatedSession(data);

        if (autenticado) {
            usuarioAtual = data.user;
            document.getElementById('mainSystem').style.display = 'block';
            atualizarInterfaceUsuario();
            setTimeout(() => {
                calculateArea();
                updateAllCalculations();
                carregarANVIs();
                updateDateTime();
            }, 500);
        } else {
            document.getElementById('mainSystem').style.display = 'none';
            redirecionarParaLoginCentral();
        }
    } catch (e) {
        console.error('Erro ao verificar sessao:', e);
        document.getElementById('mainSystem').style.display = 'none';
        redirecionarParaLoginCentral();
    }
}

async function fazerLogout() {
    try {
        await ViabixApiCore.logout();
    } catch (e) {
        console.error('Erro no logout:', e);
    }
    usuarioAtual = null;
    document.getElementById('mainSystem').style.display = 'none';
    window.location.href = 'login.html';
}

function atualizarInterfaceUsuario() {
    if (!usuarioAtual) return;

    document.getElementById('userNameDisplay').textContent = usuarioAtual.nome;
    document.getElementById('userAvatar').textContent = usuarioAtual.nome.charAt(0).toUpperCase();

    const roleBadge = document.getElementById('userRoleBadge');
    if (usuarioAtual.nivel === 'admin') {
        roleBadge.textContent = 'Admin';
        roleBadge.style.background = '#d4a017';
        document.getElementById('btnGerenciarUsuarios').style.display = 'inline-block';
    } else if (usuarioAtual.nivel === 'usuario') {
        roleBadge.textContent = 'Usuário';
        roleBadge.style.background = '#2e7d32';
        document.getElementById('btnGerenciarUsuarios').style.display = 'none';
    } else {
        roleBadge.textContent = 'Visitante';
        roleBadge.style.background = '#757575';
        document.getElementById('btnGerenciarUsuarios').style.display = 'none';
    }

    // Aplicar permissões baseadas no nível
    aplicarPermissoes(usuarioAtual.nivel);
}

function aplicarPermissoes(nivel) {
    // Implementar lógica de permissões (ex: desabilitar botões para visitantes)
    const acoesRestritas = document.querySelectorAll('.btn-primary, .btn-success, .btn-danger, .btn-warning');
    if (nivel === 'visitante') {
        acoesRestritas.forEach(btn => {
            if (btn.textContent.includes('Salvar') || btn.textContent.includes('Novo') || btn.textContent.includes('Excluir')) {
                btn.disabled = true;
                btn.title = 'Ação restrita para visitantes';
            }
        });
    } else {
        acoesRestritas.forEach(btn => btn.disabled = false);
    }
}

// =========================================
// GERENCIAMENTO DE USUÁRIOS (APENAS ADMIN)
// =========================================

async function abrirModalUsuarios() {
    if (!usuarioAtual || usuarioAtual.nivel !== 'admin') {
        alert('Acesso negado. Apenas administradores podem gerenciar usuários.');
        return;
    }

    try {
        const usuarios = await ViabixApiCore.listUsers();
        if (!Array.isArray(usuarios)) throw new Error('Erro ao carregar usuários');

        const tbody = document.getElementById('usuariosTableBody');
        tbody.innerHTML = '';

        usuarios.forEach(usuario => {
            const dataCriacao = new Date(usuario.data_criacao).toLocaleDateString('pt-BR');
            const nivelTexto = usuario.nivel === 'admin' ? 'Administrador' : (usuario.nivel === 'usuario' ? 'Usuário Padrão' : 'Visitante');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${usuario.login}</td>
                <td>${usuario.nome}</td>
                <td>${nivelTexto}</td>
                <td>${dataCriacao}</td>
                <td>
                    <button class="btn btn-sm btn-warning me-1" onclick="editarUsuario('${usuario.id}')" ${usuario.login === 'admin' ? 'disabled' : ''}>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="excluirUsuario('${usuario.id}')" ${usuario.login === 'admin' ? 'disabled' : ''}>
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        new bootstrap.Modal(document.getElementById('usuariosModal')).show();
    } catch (e) {
        console.error('Erro ao carregar usuários:', e);
        alert('Erro ao carregar usuários do servidor');
    }
}

function abrirModalNovoUsuario() {
    document.getElementById('usuarioFormModalLabel').textContent = 'Novo Usuário';
    document.getElementById('usuarioEditId').value = '';
    document.getElementById('usuarioLogin').value = '';
    document.getElementById('usuarioNome').value = '';
    document.getElementById('usuarioSenha').value = '';
    document.getElementById('usuarioNivel').value = 'usuario';
    document.getElementById('senhaHint').textContent = 'Mínimo 6 caracteres';

    new bootstrap.Modal(document.getElementById('usuarioFormModal')).show();
}

async function editarUsuario(id) {
    try {
        const usuario = await ViabixApiCore.getUserById(id);
        if (!usuario || !usuario.id) throw new Error('Erro ao carregar usuário');

        document.getElementById('usuarioFormModalLabel').textContent = 'Editar Usuário';
        document.getElementById('usuarioEditId').value = usuario.id;
        document.getElementById('usuarioLogin').value = usuario.login;
        document.getElementById('usuarioNome').value = usuario.nome;
        document.getElementById('usuarioSenha').value = '';
        document.getElementById('usuarioNivel').value = usuario.nivel;
        document.getElementById('senhaHint').textContent = 'Deixe em branco para manter a senha atual';

        new bootstrap.Modal(document.getElementById('usuarioFormModal')).show();
    } catch (e) {
        console.error('Erro ao carregar usuário:', e);
        alert('Erro ao carregar dados do usuário');
    }
}

async function salvarUsuario() {
    const id = document.getElementById('usuarioEditId').value;
    const login = document.getElementById('usuarioLogin').value.trim();
    const nome = document.getElementById('usuarioNome').value.trim();
    const senha = document.getElementById('usuarioSenha').value.trim();
    const nivel = document.getElementById('usuarioNivel').value;

    // Validações
    if (!login || !nome) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }

    if (!id && senha.length < 6) {
        alert('A senha deve ter no mínimo 6 caracteres');
        return;
    }

    if (login.length < 3) {
        alert('O usuário deve ter no mínimo 3 caracteres');
        return;
    }

    try {
        const data = await ViabixApiCore.saveUser({ id, login, nome, senha, nivel });

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('usuarioFormModal')).hide();
            abrirModalUsuarios(); // Recarrega a lista
            mostrarNotificacao('Usuário salvo com sucesso!', 'success');
        } else {
            alert(data.message || 'Erro ao salvar usuário');
        }
    } catch (e) {
        console.error('Erro ao salvar usuário:', e);
        alert('Erro de conexão com o servidor');
    }
}

async function excluirUsuario(id) {
    if (!confirm('Tem certeza que deseja excluir este usuário?')) {
        return;
    }

    try {
        const data = await ViabixApiCore.deleteUserById(id);

        if (data.success) {
            abrirModalUsuarios(); // Recarrega a lista
            mostrarNotificacao('Usuário excluído com sucesso!', 'success');
        } else {
            alert(data.message || 'Erro ao excluir usuário');
        }
    } catch (e) {
        console.error('Erro ao excluir usuário:', e);
        alert('Erro de conexão com o servidor');
    }
}

    // Operacoes de ANVI movidas para js/anvi-operations.js.

