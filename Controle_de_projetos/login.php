<?php
/**
 * Página de Login - Sistema Viabix Unificado
 * Redireciona para a página de login unificada
 */

// Iniciar sessão com nome padronizado
session_name('viabix_session');
session_start();

// Se já estiver logado, redirecionar para o módulo apropriado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Redirecionar para login unificado
header('Location: ../login.html');
exit;
?>
                </label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="senha">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <input type="password" id="senha" name="senha" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>
        
    </div>
</body>
</html>
