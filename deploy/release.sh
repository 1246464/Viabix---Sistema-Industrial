#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/viabix}"
BRANCH="${BRANCH:-main}"
TARGET_REF="${TARGET_REF:-origin/$BRANCH}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-}"
BACKUP_SCRIPT="${BACKUP_SCRIPT:-$APP_DIR/deploy/backup-viabix.sh}"
DEPLOY_LOG="${DEPLOY_LOG:-/var/log/viabix/deploy.log}"
LOCK_FILE="${LOCK_FILE:-/tmp/viabix-deploy.lock}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.2-fpm}"
WEB_SERVICE="${WEB_SERVICE:-apache2}"
RUN_COMPOSER="${RUN_COMPOSER:-auto}"
RUN_INDEX_DEPLOY="${RUN_INDEX_DEPLOY:-true}"

mkdir -p "$(dirname "$DEPLOY_LOG")"

log() {
  printf '[%s] %s\n' "$(date -u '+%Y-%m-%dT%H:%M:%SZ')" "$*" | tee -a "$DEPLOY_LOG"
}

run() {
  log "+ $*"
  "$@"
}

if [[ "${1:-}" == "--rollback" ]]; then
  ROLLBACK_REF="${2:-}"
  if [[ -z "$ROLLBACK_REF" ]]; then
    echo "Uso: $0 --rollback <git-ref-ou-sha>" >&2
    exit 2
  fi

  cd "$APP_DIR"
  log "Rollback manual solicitado para $ROLLBACK_REF"
  run git fetch --all --prune
  run git checkout --force "$ROLLBACK_REF"
  run git clean -fd --exclude=.env --exclude=logs --exclude=uploads
  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload "$PHP_FPM_SERVICE" >/dev/null 2>&1 || true
    systemctl reload "$WEB_SERVICE" >/dev/null 2>&1 || true
  fi
  log "Rollback concluido em $(git rev-parse --short HEAD)"
  exit 0
fi

exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  log "Outro deploy esta em andamento. Abortando."
  exit 1
fi

cd "$APP_DIR"

if [[ ! -d .git ]]; then
  log "Diretorio $APP_DIR nao parece ser um checkout Git."
  exit 1
fi

PREVIOUS_REF="$(git rev-parse HEAD)"
PREVIOUS_SHORT="$(git rev-parse --short HEAD)"
NEW_REF=""

rollback_to_previous() {
  local exit_code=$?
  if [[ $exit_code -eq 0 ]]; then
    return 0
  fi

  log "Deploy falhou. Voltando para $PREVIOUS_SHORT."
  git checkout --force "$PREVIOUS_REF" >>"$DEPLOY_LOG" 2>&1 || true
  git clean -fd --exclude=.env --exclude=logs --exclude=uploads >>"$DEPLOY_LOG" 2>&1 || true
  if command -v systemctl >/dev/null 2>&1; then
    systemctl reload "$PHP_FPM_SERVICE" >>"$DEPLOY_LOG" 2>&1 || true
    systemctl reload "$WEB_SERVICE" >>"$DEPLOY_LOG" 2>&1 || true
  fi
  log "Rollback automatico concluido. Commit ativo: $(git rev-parse --short HEAD 2>/dev/null || echo desconhecido)."
  exit "$exit_code"
}

trap rollback_to_previous ERR

log "Iniciando deploy. Commit atual: $PREVIOUS_SHORT. Alvo: $TARGET_REF."

if [[ -f "$BACKUP_SCRIPT" ]]; then
  log "Executando backup pre-deploy."
  APP_DIR="$APP_DIR" bash "$BACKUP_SCRIPT" >>"$DEPLOY_LOG" 2>&1
else
  log "Backup script nao encontrado: $BACKUP_SCRIPT"
  exit 1
fi

run git fetch --all --prune
run git checkout --force "$TARGET_REF"
run git reset --hard "$TARGET_REF"
run git clean -fd --exclude=.env --exclude=logs --exclude=uploads
NEW_REF="$(git rev-parse --short HEAD)"

if [[ "$RUN_COMPOSER" == "true" || ("$RUN_COMPOSER" == "auto" && -f composer.lock) ]]; then
  if command -v composer >/dev/null 2>&1; then
    log "Instalando dependencias Composer."
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader >>"$DEPLOY_LOG" 2>&1
  else
    log "composer.lock existe, mas Composer nao esta instalado."
    exit 1
  fi
fi

if [[ "$RUN_INDEX_DEPLOY" == "true" && -f "$APP_DIR/deploy/deploy_indexes.sh" ]]; then
  log "Aplicando indices/migrations leves."
  bash "$APP_DIR/deploy/deploy_indexes.sh" >>"$DEPLOY_LOG" 2>&1 || log "deploy_indexes.sh retornou aviso; continuar."
fi

if command -v systemctl >/dev/null 2>&1; then
  log "Recarregando servicos."
  systemctl reload "$PHP_FPM_SERVICE" >>"$DEPLOY_LOG" 2>&1 || systemctl restart "$PHP_FPM_SERVICE" >>"$DEPLOY_LOG" 2>&1 || true
  systemctl reload "$WEB_SERVICE" >>"$DEPLOY_LOG" 2>&1 || systemctl restart "$WEB_SERVICE" >>"$DEPLOY_LOG" 2>&1 || true
fi

if [[ -z "$HEALTHCHECK_URL" ]]; then
  APP_URL="$(grep -E '^APP_URL=' "$APP_DIR/.env" 2>/dev/null | tail -n 1 | cut -d= -f2- || true)"
  APP_URL="${APP_URL%/}"
  HEALTHCHECK_URL="${APP_URL:-http://127.0.0.1}/api/healthcheck.php?scope=ready"
fi

log "Validando healthcheck: $HEALTHCHECK_URL"
for attempt in 1 2 3 4 5; do
  if curl -fsS --max-time 10 "$HEALTHCHECK_URL" >>"$DEPLOY_LOG" 2>&1; then
    log "Healthcheck OK na tentativa $attempt."
    trap - ERR
    log "Deploy concluido com sucesso. $PREVIOUS_SHORT -> $NEW_REF."
    exit 0
  fi

  log "Healthcheck falhou na tentativa $attempt."
  sleep 5
done

log "Healthcheck nao recuperou apos 5 tentativas."
exit 1
