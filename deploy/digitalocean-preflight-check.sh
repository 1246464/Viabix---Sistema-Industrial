#!/bin/bash

################################################################################################
# 🌊 VIABIX DIGITALOCEAN - PRE-DEPLOYMENT CHECKLIST
#
# Execute este script ANTES de fazer deploy para produção
# Valida todos os requisitos e configurações
#
# Usage:
#   bash /var/www/viabix/deploy/digitalocean-preflight-check.sh
#
################################################################################################

#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASSED=0
WARNINGS=0
FAILED=0

# Functions
pass() {
    echo -e "${GREEN}✅ PASS:${NC} $1"
    ((PASSED++))
}

warn() {
    echo -e "${YELLOW}⚠️  WARN:${NC} $1"
    ((WARNINGS++))
}

fail() {
    echo -e "${RED}❌ FAIL:${NC} $1"
    ((FAILED++))
}

# Header
echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  🌊 VIABIX DIGITALOCEAN PRE-DEPLOYMENT CHECKLIST              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}\n"

# ============================================================================
# 1. ENVIRONMENT CHECKS
# ============================================================================
echo -e "${BLUE}[1] ENVIRONMENT CHECKS${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    pass "Running with root privileges"
else
    warn "Not running as root (may have permission issues)"
fi

# Check OS
if grep -qi ubuntu /etc/os-release 2>/dev/null; then
    pass "Running Ubuntu OS"
else
    warn "Not running Ubuntu (compatibility may vary)"
fi

# Check internet connectivity
if ping -c 1 8.8.8.8 >/dev/null 2>&1; then
    pass "Internet connection available"
else
    fail "No internet connectivity (cannot proceed)"
fi

# DNS resolution
if nslookup google.com >/dev/null 2>&1; then
    pass "DNS resolution working"
else
    fail "DNS resolution failed"
fi

# ============================================================================
# 2. SYSTEM REQUIREMENTS
# ============================================================================
echo -e "\n${BLUE}[2] SYSTEM REQUIREMENTS${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Memory check
TOTAL_MEM=$(free -m | awk '/^Mem:/{print $2}')
if [ "$TOTAL_MEM" -ge 2048 ]; then
    pass "RAM: ${TOTAL_MEM}MB (minimum 2GB met)"
else
    fail "RAM: ${TOTAL_MEM}MB (minimum 2GB required)"
fi

# Disk check
DISK_FREE=$(df -m / | tail -1 | awk '{print $4}')
if [ "$DISK_FREE" -ge 20000 ]; then
    pass "Disk space: ${DISK_FREE}MB free (sufficient)"
else
    warn "Disk space: ${DISK_FREE}MB free (less than 20GB recommended)"
fi

# CPU cores
CPU_CORES=$(nproc)
if [ "$CPU_CORES" -ge 1 ]; then
    pass "CPU: ${CPU_CORES} cores available"
else
    fail "CPU: Less than 1 core (cannot run)"
fi

# ============================================================================
# 3. REQUIRED PACKAGES
# ============================================================================
echo -e "\n${BLUE}[3] REQUIRED PACKAGES${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# PHP version
if command -v php >/dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | awk '{print $2}')
    if [[ "$PHP_VERSION" == 8.2* ]]; then
        pass "PHP: $PHP_VERSION installed"
    else
        fail "PHP: Required 8.2.x, found $PHP_VERSION"
    fi
else
    fail "PHP: Not installed"
fi

# Apache
if command -v apache2ctl >/dev/null; then
    pass "Apache: Installed and available"
else
    fail "Apache: Not installed"
fi

# MySQL
if command -v mysql >/dev/null; then
    pass "MySQL Client: Installed"
else
    fail "MySQL Client: Not installed"
fi

# Redis
if command -v redis-cli >/dev/null; then
    pass "Redis CLI: Installed"
else
    fail "Redis CLI: Not installed"
fi

# Composer
if command -v composer >/dev/null; then
    COMPOSER_VERSION=$(composer -V | awk '{print $3}')
    pass "Composer: $COMPOSER_VERSION installed"
else
    fail "Composer: Not installed"
fi

# Git
if command -v git >/dev/null; then
    pass "Git: Installed"
else
    fail "Git: Not installed"
fi

# cURL
if command -v curl >/dev/null; then
    pass "cURL: Installed"
else
    fail "cURL: Not installed"
fi

# Certbot
if command -v certbot >/dev/null; then
    pass "Certbot: Installed"
else
    fail "Certbot: Not installed (needed for SSL)"
fi

# ============================================================================
# 4. SERVICE CHECK
# ============================================================================
echo -e "\n${BLUE}[4] SERVICES STATUS${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Apache
if systemctl is-active apache2 >/dev/null 2>&1; then
    pass "Apache: Running ✓"
else
    warn "Apache: Not running (should start automatically)"
fi

# PHP-FPM
if systemctl is-active php8.2-fpm >/dev/null 2>&1; then
    pass "PHP-FPM: Running ✓"
else
    warn "PHP-FPM: Not running"
fi

# Redis
if systemctl is-active redis-server >/dev/null 2>&1; then
    pass "Redis: Running ✓"
else
    warn "Redis: Not running"
fi

# MySQL (if local)
if systemctl is-active mysql >/dev/null 2>&1; then
    pass "MySQL: Running ✓"
else
    # Se usar Managed Database, não precisa local
    warn "MySQL: Not running (OK if using DigitalOcean Managed DB)"
fi

# ============================================================================
# 5. FILESYSTEM PERMISSIONS
# ============================================================================
echo -e "\n${BLUE}[5] FILESYSTEM PERMISSIONS${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# App directory exists
if [ -d "/var/www/viabix" ]; then
    pass "Application directory: /var/www/viabix exists"
else
    fail "Application directory: /var/www/viabix NOT found"
    exit 1
fi

# .env file exists
if [ -f "/var/www/viabix/.env" ]; then
    pass ".env file: Exists"
    
    # .env permissions
    PERMS=$(stat -c %a /var/www/viabix/.env)
    if [ "$PERMS" == "400" ] || [ "$PERMS" == "600" ]; then
        pass ".env permissions: $PERMS (secure)"
    else
        warn ".env permissions: $PERMS (should be 400 or 600)"
    fi
else
    fail ".env file: NOT found in /var/www/viabix"
fi

# API directory writable
if [ -w "/var/www/viabix/api" ]; then
    pass "API directory: Writable"
else
    warn "API directory: Not writable"
fi

# Logs directory
if [ -d "/var/log/viabix" ]; then
    pass "Logs directory: /var/log/viabix exists"
else
    fail "Logs directory: /var/log/viabix NOT found"
fi

# ============================================================================
# 6. CONFIGURATION VALIDATION
# ============================================================================
echo -e "\n${BLUE}[6] CONFIGURATION VALIDATION${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# .env loaded
if [ -f "/var/www/viabix/.env" ]; then
    source /var/www/viabix/.env 2>/dev/null
    
    # Check critical values
    if [ -z "$APP_ENV" ] || [ "$APP_ENV" != "production" ]; then
        fail "APP_ENV: Must be 'production', is '$APP_ENV'"
    else
        pass "APP_ENV: production"
    fi
    
    # APP_DEBUG check
    if [ "$APP_DEBUG" == "false" ]; then
        pass "APP_DEBUG: false"
    else
        fail "APP_DEBUG: Must be false, is '$APP_DEBUG'"
    fi
    
    # Database credentials
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "CHANGE_ME" ]; then
        pass "DB_HOST: Configured"
    else
        fail "DB_HOST: Not configured"
    fi
    
    if [ -n "$DB_NAME" ] && [ "$DB_NAME" != "CHANGE_ME" ]; then
        pass "DB_NAME: Configured"
    else
        fail "DB_NAME: Not configured"
    fi
    
    if [ -n "$DB_USER" ] && [ "$DB_USER" != "CHANGE_ME" ]; then
        pass "DB_USER: Configured"
    else
        fail "DB_USER: Not configured"
    fi
    
    if [ -n "$DB_PASS" ] && [ "$DB_PASS" != "CHANGE_ME" ]; then
        pass "DB_PASS: Configured (hidden)"
    else
        fail "DB_PASS: Not configured"
    fi
    
    # HTTPS check
    if [[ "$APP_URL" == https://* ]]; then
        pass "APP_URL: Uses HTTPS"
    else
        fail "APP_URL: Must use HTTPS, found '$APP_URL'"
    fi
else
    fail ".env: Cannot read .env file"
fi

# ============================================================================
# 7. DATABASE CONNECTION TEST
# ============================================================================
echo -e "\n${BLUE}[7] DATABASE CONNECTION TEST${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" 2>/dev/null; then
        pass "Database connection: ✓"
    else
        fail "Database connection: Failed (check credentials and firewall)"
    fi
else
    warn "Database credentials incomplete - skipping connection test"
fi

# ============================================================================
# 8. SSL CERTIFICATE
# ============================================================================
echo -e "\n${BLUE}[8] SSL CERTIFICATE${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if command -v certbot >/dev/null; then
    CERT_STATUS=$(certbot certificates 2>&1 | grep -c "No matching certificate found" || echo "0")
    if [ "$CERT_STATUS" == "0" ]; then
        EXPIRY=$(certbot certificates 2>&1 | grep "Expiry" | head -1 | awk '{print $NF}')
        pass "SSL Certificate: Valid (expires $EXPIRY)"
    else
        fail "SSL Certificate: Not found (run certbot)"
    fi
else
    fail "Certbot: Not installed (needed for SSL)"
fi

# ============================================================================
# 9. BACKUP CONFIGURATION
# ============================================================================
echo -e "\n${BLUE}[9] BACKUP CONFIGURATION${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -f "/usr/local/bin/viabix-backup.sh" ]; then
    pass "Backup script: Found"
    
    if [ -x "/usr/local/bin/viabix-backup.sh" ]; then
        pass "Backup script: Executable"
    else
        warn "Backup script: Not executable (chmod +x needed)"
    fi
else
    fail "Backup script: Not found at /usr/local/bin/viabix-backup.sh"
fi

# Check S3 credentials
if [ -n "$BACKUP_S3_KEY" ] && [ "$BACKUP_S3_KEY" != "CHANGE_ME" ]; then
    pass "S3 Credentials: Configured"
else
    warn "S3 Credentials: Not configured (backups won't work)"
fi

# ============================================================================
# 10. MONITORING
# ============================================================================
echo -e "\n${BLUE}[10] MONITORING${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Sentry
if [ -n "$SENTRY_DSN" ] && [ "$SENTRY_DSN" != "https://seu-public-key*" ]; then
    pass "Sentry: Configured"
else
    warn "Sentry: Not configured (error tracking disabled)"
fi

# Log rotation
if [ -f "/etc/logrotate.d/apache2" ]; then
    pass "Log rotation: Apache configured"
else
    warn "Log rotation: Apache not configured"
fi

# ============================================================================
# SUMMARY
# ============================================================================
echo -e "\n"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                         RESULTS SUMMARY                       ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✅ PASSED:  $PASSED${NC}"
echo -e "${YELLOW}⚠️  WARNED: $WARNINGS${NC}"
echo -e "${RED}❌ FAILED:  $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🚀 READY FOR DEPLOYMENT${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Verify DigitalOcean infrastructure is ready"
    echo "2. Run: systemctl restart apache2 php8.2-fpm redis-server"
    echo "3. Monitor: tail -f /var/log/apache2/viabix-error.log"
    echo "4. Healthcheck: curl https://app.viabix.com.br/api/healthcheck"
    exit 0
else
    echo -e "${RED}❌ FIX ERRORS BEFORE DEPLOYMENT${NC}"
    exit 1
fi
