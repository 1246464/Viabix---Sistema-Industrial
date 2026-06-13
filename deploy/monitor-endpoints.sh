#!/usr/bin/env bash
set -Eeuo pipefail

BASE_URL="${BASE_URL:-https://app.viabix.com.br}"
LOG_FILE="${LOG_FILE:-/var/log/viabix/endpoint-monitor.log}"
TIMEOUT_SECONDS="${TIMEOUT_SECONDS:-10}"
ENDPOINTS="${ENDPOINTS:-/api/healthcheck.php?scope=live /api/healthcheck.php?scope=ready /api/index.php /index.html /login.html}"

mkdir -p "$(dirname "$LOG_FILE")"

status=0
timestamp="$(date -u '+%Y-%m-%dT%H:%M:%SZ')"

for endpoint in $ENDPOINTS; do
  url="${BASE_URL%/}${endpoint}"
  http_code="$(curl -k -sS -o /tmp/viabix-monitor-response.$$ -w '%{http_code}' --max-time "$TIMEOUT_SECONDS" "$url" || true)"

  if [[ "$http_code" =~ ^[23] ]]; then
    printf '[%s] OK %s HTTP %s\n' "$timestamp" "$url" "$http_code" >> "$LOG_FILE"
  else
    status=1
    body_preview="$(head -c 300 /tmp/viabix-monitor-response.$$ 2>/dev/null | tr '\n' ' ')"
    printf '[%s] FAIL %s HTTP %s %s\n' "$timestamp" "$url" "${http_code:-000}" "$body_preview" >> "$LOG_FILE"
  fi

  rm -f /tmp/viabix-monitor-response.$$
done

exit "$status"
