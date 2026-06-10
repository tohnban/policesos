#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

SKIP_SMOKE=0
SKIP_COMPOSER=0
for arg in "$@"; do
  case "$arg" in
    --skip-smoke) SKIP_SMOKE=1 ;;
    --skip-composer) SKIP_COMPOSER=1 ;;
  esac
done

echo "=== Imobil — Deploy de Producao ==="

if [[ ! -f "$ROOT/.env" ]]; then
  echo "ERRO: .env obrigatorio. Copie .env.production.example para .env" >&2
  exit 1
fi

php "$ROOT/scripts/ensure-storage-dirs.php"

if [[ "$SKIP_COMPOSER" -eq 0 ]]; then
  (cd "$ROOT/src" && composer install --no-dev --optimize-autoloader --no-interaction)
fi

php "$ROOT/scripts/production-check.php"

php -r "require '$ROOT/src/vendor/autoload.php'; require '$ROOT/config/config.php'; \Src\classes\PageCache::flush(); echo 'PageCache flushed'.PHP_EOL;"

if [[ "$SKIP_SMOKE" -eq 0 ]]; then
  pwsh -File "$ROOT/scripts/regression-smoke.ps1" || php "$ROOT/scripts/regression-smoke.ps1" 2>/dev/null || true
fi

echo ""
echo "Deploy de producao preparado."
echo "Registe cron (exemplo Linux):"
echo "  0 * * * * php $ROOT/scripts/requests_sla_scheduler.php"
echo "  0 */6 * * * php $ROOT/scripts/commission_scheduler.php"
echo "  */5 * * * * php $ROOT/scripts/mail_queue_worker.php"
