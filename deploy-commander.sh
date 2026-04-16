#!/bin/bash

################################################################################################
# 🌊 VIABIX DEPLOY COMMANDER - DigitalOcean Edition
#
# Menu interativo para deploy do Viabix em DigitalOcean
# 
# Usage:
#    bash deploy-commander.sh
#
################################################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

clear

cat << "EOF"

    ██╗   ██╗██╗ █████╗ ██████╗ ██╗██╗  ██╗
    ██║   ██║██║██╔══██╗██╔══██╗██║╚██╗██╔╝
    ██║   ██║██║███████║██████╔╝██║ ╚███╔╝ 
    ╚██╗ ██╔╝██║██╔══██║██╔══██╗██║ ██╔██╗ 
     ╚████╔╝ ██║██║  ██║██████╔╝██║██╔╝ ██╗
      ╚═══╝  ╚═╝╚═╝  ╚═╝╚═════╝ ╚═╝╚═╝  ╚═╝
                                            
      🌊 DEPLOY COMMANDER - DigitalOcean Edition
      v1.0.0 - Automated Deployment Tool
      
EOF

# Check root
if [ "$EUID" -ne 0 ]; then 
   echo -e "${RED}❌ Este script deve ser executado como root${NC}"
   exit 1
fi

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Bem-vindo ao Viabix Deploy Commander                         ║${NC}"
echo -e "${BLUE}║  Ambiente: DigitalOcean                                        ║${NC}"
echo -e "${BLUE}║  Versão: 1.0.0                                                ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Main menu
while true; do
    echo -e "${CYAN}┌─ MENU PRINCIPAL${NC}"
    echo ""
    echo "  1) ${GREEN}🚀 INITIAL SETUP${NC} - Primeira instalação (recomendado)"
    echo "  2) ${YELLOW}✅ PRE-DEPLOYMENT CHECK${NC} - Validar antes de produção"
    echo "  3) ${BLUE}🔄 UPDATE APPLICATION${NC} - Atualizar código para nova versão"
    echo "  4) ${CYAN}📊 SYSTEM STATUS${NC} - Ver status dos serviços"
    echo "  5) ${RED}⚠️  TROUBLESHOOTING${NC} - Diagnóstico de problemas"
    echo "  6) ${YELLOW}📚 DOCUMENTATION${NC} - Abrir guias de referência"
    echo "  7) ${RED}❌ EXIT${NC} - Sair"
    echo ""
    read -p "Selecione uma opção [1-7]: " choice

    case $choice in
        1)
            clear
            initial_setup
            ;;
        2)
            clear
            preflight_check
            ;;
        3)
            clear
            update_application
            ;;
        4)
            clear
            system_status
            ;;
        5)
            clear
            troubleshooting_menu
            ;;
        6)
            clear
            documentation_menu
            ;;
        7)
            echo -e "${GREEN}✅ Até logo!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Opção inválida${NC}"
            ;;
    esac
done

# ============================================================================
# FUNCTIONS
# ============================================================================

initial_setup() {
    echo -e "${BLUE}🚀 INITIAL SETUP - VIABIX ON DIGITALOCEAN${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Este script irá:"
    echo "  ✅ Atualizar sistema"
    echo "  ✅ Instalar PHP 8.2, Apache, Redis"
    echo "  ✅ Configurar banco de dados"
    echo "  ✅ Instalar certificado SSL"
    echo "  ✅ Configurar backups automáticos"
    echo ""
    
    read -p "Tem certeza que quer continuar? (s/n): " confirm
    if [ "$confirm" != "s" ] && [ "$confirm" != "S" ]; then
        return
    fi
    
    echo -e "\n${YELLOW}⏳ Iniciando setup... Isso pode levar 15-20 minutos${NC}\n"
    
    # Run setup script
    if [ -f "./deploy/digitalocean-setup.sh" ]; then
        bash ./deploy/digitalocean-setup.sh
    else
        curl -s https://raw.githubusercontent.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash
    fi
    
    echo -e "\n${GREEN}✅ Setup inicial concluído!${NC}"
    echo ""
    read -p "Pressione ENTER para continuar..."
}

preflight_check() {
    echo -e "${CYAN}✅ PRE-DEPLOYMENT CHECK${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    if [ -f "./deploy/digitalocean-preflight-check.sh" ]; then
        bash ./deploy/digitalocean-preflight-check.sh
    else
        echo -e "${RED}❌ Preflight check script not found${NC}"
    fi
    
    echo ""
    read -p "Pressione ENTER para continuar..."
}

update_application() {
    echo -e "${BLUE}🔄 UPDATE APPLICATION${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Atualizações disponíveis:"
    echo "  1) Update código (git pull)"
    echo "  2) Update dependencies (composer)"
    echo "  3) Update sistema operacional (apt upgrade)"
    echo "  4) Voltar"
    echo ""
    read -p "Selecione [1-4]: " update_choice
    
    case $update_choice in
        1)
            echo -e "${YELLOW}Atualizando código...${NC}"
            cd /var/www/viabix
            git pull origin main
            systemctl restart apache2 php8.2-fpm
            echo -e "${GREEN}✅ Código atualizado${NC}"
            ;;
        2)
            echo -e "${YELLOW}Atualizando dependências...${NC}"
            cd /var/www/viabix
            composer install --no-dev --optimize-autoloader
            systemctl restart php8.2-fpm
            echo -e "${GREEN}✅ Dependências atualizadas${NC}"
            ;;
        3)
            echo -e "${YELLOW}Atualizando sistema...${NC}"
            apt-get update && apt-get upgrade -y
            echo -e "${GREEN}✅ Sistema atualizado${NC}"
            ;;
        4)
            return
            ;;
    esac
    
    echo ""
    read -p "Pressione ENTER para continuar..."
}

system_status() {
    echo -e "${CYAN}📊 SYSTEM STATUS${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    
    echo -e "${BLUE}💾 MEMORY USAGE:${NC}"
    free -h
    
    echo -e "\n${BLUE}💿 DISK USAGE:${NC}"
    df -h /
    
    echo -e "\n${BLUE}🔥 CPU LOAD:${NC}"
    uptime
    
    echo -e "\n${BLUE}🔧 SERVICES:${NC}"
    for service in apache2 php8.2-fpm redis-server mysql; do
        if systemctl is-active --quiet $service; then
            echo -e "  ${GREEN}✅${NC} $service"
        else
            echo -e "  ${RED}❌${NC} $service"
        fi
    done
    
    echo -e "\n${BLUE}📝 LOGS (últimas 5 linhas):${NC}"
    tail -5 /var/log/viabix/error.log 2>/dev/null || echo "  No logs yet"
    
    echo ""
    read -p "Pressione ENTER para continuar..."
}

troubleshooting_menu() {
    echo -e "${RED}⚠️  TROUBLESHOOTING${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "  1) Reiniciar Apache"
    echo "  2) Reiniciar PHP-FPM"
    echo "  3) Reiniciar Redis"
    echo "  4) Reiniciar tudo"
    echo "  5) Ver logs de erro"
    echo "  6) Testar conexão com database"
    echo "  7) Limpar cache"
    echo "  8) Voltar"
    echo ""
    read -p "Selecione [1-8]: " troubleshoot_choice
    
    case $troubleshoot_choice in
        1)
            systemctl restart apache2
            echo -e "${GREEN}✅ Apache reiniciado${NC}"
            ;;
        2)
            systemctl restart php8.2-fpm
            echo -e "${GREEN}✅ PHP-FPM reiniciado${NC}"
            ;;
        3)
            systemctl restart redis-server
            echo -e "${GREEN}✅ Redis reiniciado${NC}"
            ;;
        4)
            systemctl restart apache2 php8.2-fpm redis-server
            echo -e "${GREEN}✅ Todos os serviços reiniciados${NC}"
            ;;
        5)
            echo -e "${YELLOW}Últimos 50 erros:${NC}"
            tail -50 /var/log/apache2/viabix-error.log
            ;;
        6)
            echo -e "${YELLOW}Testando conexão com database...${NC}"
            mysql -h localhost -u viabix_app -p -e "SELECT 1" && echo -e "${GREEN}✅ Conexão OK${NC}" || echo -e "${RED}❌ Falha na conexão${NC}"
            ;;
        7)
            redis-cli FLUSHALL
            echo -e "${GREEN}✅ Cache limpo${NC}"
            ;;
        8)
            return
            ;;
    esac
    
    echo ""
    read -p "Pressione ENTER para continuar..."
}

documentation_menu() {
    echo -e "${YELLOW}📚 DOCUMENTATION${NC}"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "Guias disponíveis:"
    echo ""
    echo "  1) 📖 DigitalOcean Quick Start (30 min)"
    echo "  2) 📖 DigitalOcean Full Deployment Guide"
    echo "  3) 📖 Production Deployment Guide"
    echo "  4) 📖 Deployment Checklist"
    echo "  5) 📖 Troubleshooting Guide"
    echo "  6) Voltar"
    echo ""
    read -p "Selecione [1-6]: " doc_choice
    
    case $doc_choice in
        1)
            less DIGITALOCEAN_QUICK_START.md
            ;;
        2)
            less DIGITALOCEAN_DEPLOYMENT.md
            ;;
        3)
            less PRODUCTION_DEPLOYMENT.md
            ;;
        4)
            less DEPLOYMENT_CHECKLIST.md
            ;;
        5)
            less PRODUCTION_DEPLOYMENT.md  # Tem seção de troubleshooting
            ;;
        6)
            return
            ;;
    esac
}

