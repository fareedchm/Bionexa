#!/usr/bin/env bash
# Installs and configures the Bionexa WordPress site.
#
# Local (Docker):   cp .env.example .env && docker compose up -d && ./setup/install.sh
# Live server (SSH with WP-CLI, from the WordPress root after copying the theme + plugin):
#                   WP_CLI="wp" SETUP_DIR=/path/to/repo/setup SKIP_CORE_INSTALL=1 /path/to/repo/setup/install.sh
#
# Safe to re-run: existing pages, categories and products (matched by slug/SKU) are left alone.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [[ -f .env ]]; then
	set -a; source .env; set +a
fi

SITE_URL="${SITE_URL:-http://localhost:${WP_PORT:-8080}}"
SITE_TITLE="${SITE_TITLE:-Bionexa}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-change-me-now}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
STORE_CURRENCY="${STORE_CURRENCY:-USD}"
SKIP_CORE_INSTALL="${SKIP_CORE_INSTALL:-0}"

if [[ -n "${WP_CLI:-}" ]]; then
	read -r -a WP <<< "$WP_CLI"
	SETUP_DIR="${SETUP_DIR:-$ROOT/setup}"
else
	WP=(docker compose run --rm -T cli wp)
	SETUP_DIR="/setup"
fi

wp() { "${WP[@]}" "$@"; }

echo "→ Waiting for WordPress and the database…"
for i in $(seq 1 30); do
	if wp core version >/dev/null 2>&1 && wp db check >/dev/null 2>&1; then
		break
	fi
	if [[ $i -eq 30 ]]; then
		echo "WordPress/database not reachable. Is 'docker compose up -d' running?" >&2
		exit 1
	fi
	sleep 3
done

if [[ "$SKIP_CORE_INSTALL" != "1" ]] && ! wp core is-installed >/dev/null 2>&1; then
	echo "→ Installing WordPress"
	wp core install --url="$SITE_URL" --title="$SITE_TITLE" \
		--admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASSWORD" \
		--admin_email="$ADMIN_EMAIL" --skip-email
fi

echo "→ Permalinks, timezone"
wp rewrite structure '/%postname%/' >/dev/null
wp option update timezone_string 'UTC' >/dev/null

echo "→ WooCommerce"
if ! wp plugin is-installed woocommerce; then
	# Uses setup/woocommerce.zip if you put one there (offline installs), otherwise downloads it.
	if [[ -f "$ROOT/setup/woocommerce.zip" ]]; then
		wp plugin install "$SETUP_DIR/woocommerce.zip" --activate
	else
		wp plugin install woocommerce --activate
	fi
else
	wp plugin activate woocommerce >/dev/null
fi

echo "→ Bionexa theme + plugin"
wp plugin activate bionexa-core
wp theme activate bionexa
wp plugin delete hello >/dev/null 2>&1 || true

echo "→ Store settings, pages, categories and sample products"
wp eval-file "$SETUP_DIR/setup.php" "$STORE_CURRENCY"

wp rewrite flush >/dev/null
wp cache flush >/dev/null 2>&1 || true

cat <<EOF

✅ Bionexa is ready
   Site:   $SITE_URL
   Admin:  $SITE_URL/wp-admin   (user: $ADMIN_USER)

Next steps (see README.md):
   1. WooCommerce → Settings → Payments: connect Stripe and/or PayPal.
   2. Replace sample product files and prices (Products → All Products).
   3. Edit the header/footer/home page in Appearance → Editor and Pages.
EOF
