<?php
/**
 * =======================================================
 * TESTES UNITÁRIOS - SISTEMA DE AUTENTICAÇÃO E AUTORIZAÇÃO
 * =======================================================
 * 
 * Use com PHPUnit:
 * vendor/bin/phpunit api/tests/AuthSystemTest.php
 * 
 * @requires PHPUnit >= 9.0
 */

namespace ViabixTests;

use PHPUnit\Framework\TestCase;

/**
 * Testes do sistema centralizado de autenticação
 */
class AuthSystemTest extends TestCase {

    protected static $testUserId = 'test-user-123';
    protected static $testTenantId = 'test-tenant-456';
    protected static $testEmail = 'test@example.com';

    /**
     * Setup inicial para os testes
     */
    public static function setUpBeforeClass(): void {
        // Carregar o arquivo de auth
        require_once __DIR__ . '/../auth_system.php';
        
        // Inicializar sessão de teste
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // ===================================================
    // TESTES: viabixRequireAuthentication()
    // ===================================================

    public function testRequireAuthenticationWithValidSession() {
        // Setup
        $_SESSION['user_id'] = self::$testUserId;
        $_SESSION['tenant_id'] = self::$testTenantId;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_permissions'] = ['usuarios:view'];

        // Test
        $user = viabixGetCurrentUser();
        
        $this->assertNotNull($user);
        $this->assertEquals(self::$testUserId, $user['id']);
        $this->assertEquals(self::$testTenantId, $user['tenant_id']);
        $this->assertEquals('admin', $user['role']);
    }

    public function testRequireAuthenticationWithoutSession() {
        // Clear session
        $_SESSION = [];
        
        // Test
        $user = viabixGetCurrentUser();
        
        $this->assertNull($user);
    }

    public function testRequireAuthenticationWithMissingTenantId() {
        // Setup
        $_SESSION['user_id'] = self::$testUserId;
        $_SESSION['tenant_id'] = null; // Faltando

        // Test
        $user = viabixGetCurrentUser();
        
        // Deve retornar null porque tenant_id está faltando
        $this->assertNull($user);
    }

    // ===================================================
    // TESTES: viabixHasPermission()
    // ===================================================

    public function testHasPermissionAdminHasAll() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'admin',
            'permissions' => [],
        ];

        // Admin deve ter todas as permissões
        $this->assertTrue(viabixHasPermission('usuarios', 'create', $user));
        $this->assertTrue(viabixHasPermission('usuarios', 'delete', $user));
        $this->assertTrue(viabixHasPermission('anvis', 'export', $user));
        $this->assertTrue(viabixHasPermission('admin_saas', 'suspend_tenant', $user));
    }

    public function testHasPermissionWithValidPermission() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'editor',
            'permissions' => [
                'usuarios:view',
                'anvis:view',
                'anvis:create',
            ],
        ];

        // Deve ter estas
        $this->assertTrue(viabixHasPermission('usuarios', 'view', $user));
        $this->assertTrue(viabixHasPermission('anvis', 'view', $user));
        $this->assertTrue(viabixHasPermission('anvis', 'create', $user));

        // Não deve ter estas
        $this->assertFalse(viabixHasPermission('usuarios', 'create', $user));
        $this->assertFalse(viabixHasPermission('anvis', 'delete', $user));
    }

    public function testHasPermissionInvalidResource() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'admin',
            'permissions' => [],
        ];

        // Resource inválido
        $this->assertFalse(viabixHasPermission('invalid_resource', 'view', $user));
        $this->assertFalse(viabixHasPermission('usuarios', 'invalid_action', $user));
    }

    public function testHasPermissionWithNullUser() {
        $_SESSION = []; // Sem sessão

        $result = viabixHasPermission('usuarios', 'view', null);
        
        $this->assertFalse($result);
    }

    // ===================================================
    // TESTES: viabixValidateResourceTenant()
    // ===================================================

    public function testValidateResourceTenantMatch() {
        $result = viabixValidateResourceTenant('tenant-1', 'tenant-1');
        
        $this->assertTrue($result);
    }

    public function testValidateResourceTenantMismatch() {
        $result = viabixValidateResourceTenant('tenant-1', 'tenant-2');
        
        $this->assertFalse($result);
    }

    // Testar IDOR: tipo de dados diferentes
    public function testValidateResourceTenantTypeDifference() {
        // Comparação strict! Tipo diferente = false
        $result = viabixValidateResourceTenant(123, '123');
        
        $this->assertFalse($result);
    }

    // ===================================================
    // TESTES: viabixGetCurrentTenantId()
    // ===================================================

    public function testGetCurrentTenantId() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => 'my-tenant-id',
            'role' => 'admin',
            'permissions' => [],
        ];

        $tenant_id = viabixGetCurrentTenantId($user);
        
        $this->assertEquals('my-tenant-id', $tenant_id);
    }

    public function testGetCurrentTenantIdNull() {
        $user = [
            'id' => self::$testUserId,
            'role' => 'admin',
            'permissions' => [],
        ];

        $tenant_id = viabixGetCurrentTenantId($user);
        
        $this->assertNull($tenant_id);
    }

    // ===================================================
    // TESTES: viabixValidateEnum()
    // ===================================================

    public function testValidateEnumValid() {
        $allowed = ['admin', 'editor', 'visualizador'];
        
        $result = viabixValidateEnum('admin', $allowed);
        
        $this->assertEquals('admin', $result);
    }

    public function testValidateEnumInvalid() {
        $allowed = ['admin', 'editor', 'visualizador'];
        
        $result = viabixValidateEnum('super_admin', $allowed);
        
        $this->assertNull($result);
    }

    public function testValidateEnumCaseSensitive() {
        $allowed = ['admin', 'editor'];
        
        // Deve ser case-sensitive!
        $result = viabixValidateEnum('ADMIN', $allowed);
        
        $this->assertNull($result);
    }

    // ===================================================
    // TESTES: viabixValidateId()
    // ===================================================

    public function testValidateIdUUID() {
        $valid_uuid = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        
        $result = viabixValidateId($valid_uuid);
        
        $this->assertTrue($result);
    }

    public function testValidateIdNumeric() {
        $result = viabixValidateId(123);
        
        $this->assertTrue($result);
    }

    public function testValidateIdZero() {
        $result = viabixValidateId(0);
        
        $this->assertFalse($result);
    }

    public function testValidateIdNegative() {
        $result = viabixValidateId(-1);
        
        $this->assertFalse($result);
    }

    public function testValidateIdInvalidString() {
        $result = viabixValidateId('not-a-uuid');
        
        $this->assertFalse($result);
    }

    // ===================================================
    // TESTES: viabixPopulateSessionWithPermissions()
    // ===================================================

    public function testPopulateSessionWithPermissions() {
        $user = [
            'id' => 'user-123',
            'email' => 'user@test.com',
            'nome' => 'Test User',
            'login' => 'testuser',
            'role' => 'editor',
        ];

        $tenant = [
            'id' => 'tenant-456',
            'nome_fantasia' => 'Test Tenant',
            'status' => 'ativo',
            'slug' => 'test-tenant',
            'subscription_status' => 'ativa',
            'plan_code' => 'pro',
            'plan_name' => 'Pro',
            'features' => ['anvi' => true, 'relatorio' => false],
        ];

        // Mock da função de carregamento de permissões do BD
        // (não queremos acessar BD real nos testes)
        // Por isso retornar array vazio
        $_SESSION = [];
        viabixPopulateSessionWithPermissions($user, $tenant);

        // Validar que dados foram populados
        $this->assertEquals('user-123', $_SESSION['user_id']);
        $this->assertEquals('user@test.com', $_SESSION['email']);
        $this->assertEquals('Test User', $_SESSION['user_nome']);
        $this->assertEquals('editor', $_SESSION['user_role']);
        $this->assertEquals('tenant-456', $_SESSION['tenant_id']);
        $this->assertEquals('Test Tenant', $_SESSION['tenant_nome']);
        $this->assertEquals('ativo', $_SESSION['tenant_status']);
        $this->assertEquals('ativa', $_SESSION['subscription_status']);
        $this->assertEquals('pro', $_SESSION['plan_code']);
        $this->assertIsArray($_SESSION['user_permissions']);
        $this->assertIsArray($_SESSION['features']);
    }

    // ===================================================
    // TESTES: viabixGetUserPermissions()
    // ===================================================

    public function testGetUserPermissionsAdmin() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'admin',
            'permissions' => [],
        ];

        // Admin não precisa ter permissions no array, tem tudo
        $perms = viabixGetUserPermissions($user);
        
        // Deve ser array (pode estar vazio se não carregar do BD)
        $this->assertIsArray($perms);
    }

    public function testGetUserPermissionsNonAdmin() {
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'editor',
            'permissions' => ['usuarios:view', 'anvis:create'],
        ];

        $perms = viabixGetUserPermissions($user);
        
        $this->assertIsArray($perms);
        $this->assertContains('usuarios:view', $perms);
        $this->assertContains('anvis:create', $perms);
    }

    // ===================================================
    // TESTES: viabixRegenerateSessionId()
    // ===================================================

    public function testRegenerateSessionId() {
        // Inicia sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $old_id = session_id();
        
        viabixRegenerateSessionId();
        
        $new_id = session_id();
        
        // IDs devem ser diferentes após regeneração
        $this->assertNotEquals($old_id, $new_id);
    }

    // ===================================================
    // TESTES: viabixDestroySession()
    // ===================================================

    public function testDestroySession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['test_key'] = 'test_value';
        $this->assertArrayHasKey('test_key', $_SESSION);

        viabixDestroySession();

        $this->assertEmpty($_SESSION);
    }

    // ===================================================
    // TESTES: Validação de segurança (IDOR, CSRF, etc)
    // ===================================================

    public function testIdorPrevention() {
        // Simular: usuário B tenta acessar recurso de usuário A
        $user_b = [
            'id' => 'user-b',
            'tenant_id' => 'tenant-b',
            'role' => 'admin',
            'permissions' => [],
        ];

        // Recurso pertence a tenant A
        $resource_tenant_id = 'tenant-a';
        $user_b_tenant_id = $user_b['tenant_id'];

        // Validação deve falhar
        $result = viabixValidateResourceTenant($resource_tenant_id, $user_b_tenant_id);
        
        $this->assertFalse($result);
    }

    public function testPermissionEscalationPrevention() {
        // Usuário tenta se dar permissão maior que sua role permite
        $user = [
            'id' => self::$testUserId,
            'tenant_id' => self::$testTenantId,
            'role' => 'editor', // editor, não admin
            'permissions' => ['usuarios:view', 'anvis:create'],
        ];

        // Deve negar acesso a admin_saas
        $result = viabixHasPermission('admin_saas', 'view_tenants', $user);
        
        $this->assertFalse($result);
    }

    // ===================================================
    // TESTES: Validação de entrada
    // ===================================================

    public function testValidateEnumAllowsExactMatch() {
        $allowed = ['admin', 'editor', 'visualizador', 'visitante'];
        
        foreach ($allowed as $value) {
            $result = viabixValidateEnum($value, $allowed);
            $this->assertEquals($value, $result, "Deve aceitar '$value'");
        }
    }

    public function testValidateEnumRejectsInvalid() {
        $allowed = ['admin', 'editor', 'visualizador'];
        $invalid = ['Admin', 'ADMIN', 'super_admin', 'user', '', null];
        
        foreach ($invalid as $value) {
            $result = viabixValidateEnum($value, $allowed);
            $this->assertNull($result, "Deve rejeitar '$value'");
        }
    }

    // ===================================================
    // TESTES: Constantes definidas corretamente
    // ===================================================

    public function testResourcesConstantDefined() {
        $this->assertIsArray(VIABIX_RESOURCES);
        $this->assertArrayHasKey('usuarios', VIABIX_RESOURCES);
        $this->assertArrayHasKey('anvis', VIABIX_RESOURCES);
        $this->assertArrayHasKey('admin_saas', VIABIX_RESOURCES);
    }

    public function testResourcesHaveValidActions() {
        foreach (VIABIX_RESOURCES as $resource => $actions) {
            $this->assertIsArray($actions, "$resource deve ter array de actions");
            $this->assertNotEmpty($actions, "$resource não pode ter ações vazias");
            
            foreach ($actions as $action) {
                $this->assertIsString($action, "Action deve ser string");
                $this->assertNotEmpty($action, "Action não pode ser vazia");
            }
        }
    }

    public function testRolesConstantDefined() {
        $this->assertIsArray(VIABIX_ROLES);
        $this->assertArrayHasKey('admin', VIABIX_ROLES);
        $this->assertArrayHasKey('editor', VIABIX_ROLES);
        $this->assertArrayHasKey('visualizador', VIABIX_ROLES);
        $this->assertArrayHasKey('visitante', VIABIX_ROLES);
    }

    public function testRolesHaveValidPermissions() {
        foreach (VIABIX_ROLES as $role => $permissions) {
            $this->assertIsArray($permissions, "$role deve ter array de permissions");
            
            foreach ($permissions as $perm) {
                $this->assertStringContainsString(':', $perm, "Permission deve estar no formato resource:action");
                
                [$resource, $action] = explode(':', $perm);
                $this->assertArrayHasKey($resource, VIABIX_RESOURCES, "Resource '$resource' deve estar em VIABIX_RESOURCES");
                $this->assertContains($action, VIABIX_RESOURCES[$resource], "Action '$action' deve estar em recursos");
            }
        }
    }

    // ===================================================
    // TESTES: Matriz de permissões completa
    // ===================================================

    public function testAdminHasAllPermissions() {
        $admin = [
            'id' => 'admin-1',
            'tenant_id' => 'tenant-1',
            'role' => 'admin',
            'permissions' => [],
        ];

        // Testar todos os recursos e ações
        foreach (VIABIX_RESOURCES as $resource => $actions) {
            foreach ($actions as $action) {
                $result = viabixHasPermission($resource, $action, $admin);
                $this->assertTrue($result, "Admin deve ter $resource:$action");
            }
        }
    }

    public function testEditorHasProperPermissions() {
        $editor = [
            'id' => 'editor-1',
            'tenant_id' => 'tenant-1',
            'role' => 'editor',
            'permissions' => VIABIX_ROLES['editor'],
        ];

        // Deve ter view de usuarios
        $this->assertTrue(viabixHasPermission('usuarios', 'view', $editor));

        // Não deve ter delete de usuarios
        $this->assertFalse(viabixHasPermission('usuarios', 'delete', $editor));

        // Deve ter create de anvis
        $this->assertTrue(viabixHasPermission('anvis', 'create', $editor));
    }

    public function testVisualizadorHasOnlyViewPermissions() {
        $viz = [
            'id' => 'viz-1',
            'tenant_id' => 'tenant-1',
            'role' => 'visualizador',
            'permissions' => VIABIX_ROLES['visualizador'],
        ];

        // Deve ter view
        $this->assertTrue(viabixHasPermission('anvis', 'view', $viz));
        $this->assertTrue(viabixHasPermission('usuarios', 'view', $viz));

        // Não deve ter create/update/delete
        $this->assertFalse(viabixHasPermission('anvis', 'create', $viz));
        $this->assertFalse(viabixHasPermission('usuarios', 'create', $viz));
        $this->assertFalse(viabixHasPermission('anvis', 'delete', $viz));
    }

    public function testVisitanteHasMinimalPermissions() {
        $visitor = [
            'id' => 'visitor-1',
            'tenant_id' => 'tenant-1',
            'role' => 'visitante',
            'permissions' => VIABIX_ROLES['visitante'],
        ];

        // Deve ter view de anvis e projetos
        $this->assertTrue(viabixHasPermission('anvis', 'view', $visitor));
        $this->assertTrue(viabixHasPermission('projetos', 'view', $visitor));

        // Não deve ter nada mais
        $this->assertFalse(viabixHasPermission('usuarios', 'view', $visitor));
        $this->assertFalse(viabixHasPermission('anvis', 'create', $visitor));
        $this->assertFalse(viabixHasPermission('relatorios', 'view', $visitor));
    }
}

?>
