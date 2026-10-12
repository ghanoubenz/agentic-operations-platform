#!/usr/bin/env bash
# Runs the end-to-end suite against the local staging stack (demo content required).
set -euo pipefail
cd "$(dirname "$0")/.."
# Fresh state: clear rate-limit/duplicate transients and restore demo stock used by the suite.
docker compose --profile tools run --rm cli bash -c '
  wp transient delete --all >/dev/null
  wp user get gestion >/dev/null 2>&1 || wp user create gestion gestion@example.com --role=shop_manager --user_pass=staging-gestion-pass >/dev/null
  wp eval "foreach ( array( \"DEMO-001\" => 12, \"DEMO-006\" => 40, \"DEMO-008\" => 25 ) as \$sku => \$q ) { \$p = wc_get_product( wc_get_product_id_by_sku( \$sku ) ); if ( \$p ) { \$p->set_stock_quantity( \$q ); \$p->save(); } }"
' 2>&1 | grep -v Container || true
curl -s -X DELETE http://localhost:${MAIL_UI_PORT:-8025}/api/v1/messages >/dev/null || true
cd tests
[[ -d node_modules ]] || npm ci --no-audit --no-fund
npx playwright test "$@"
