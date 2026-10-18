#!/usr/bin/env bash
set -euo pipefail

# PharmaInsights Hub - Automated WordPress Provisioning Script via WP-CLI
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

cd "${ROOT_DIR}"

if [ -f .env ]; then
  # Load environment variables safely
  export $(grep -v '^#' .env | xargs)
fi

WP_SITE_TITLE="${WP_SITE_TITLE:-PharmaInsights Hub}"
WP_SITE_URL="${WP_SITE_URL:-http://localhost:8080}"
WP_ADMIN_USER="${WP_ADMIN_USER:-pi_admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-PharmaInsightSecure2026!LocalDev}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@pharmainsights-hub.internal}"
WP_TIMEZONE="${WP_TIMEZONE:-Asia/Dubai}"
WP_LOCALE="${WP_LOCALE:-en_US}"

echo "=========================================================="
echo " Starting WordPress Setup for: ${WP_SITE_TITLE}"
echo " Site URL: ${WP_SITE_URL}"
echo " Timezone: ${WP_TIMEZONE}"
echo "=========================================================="

# Helper function to run WP-CLI
wp() {
  docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp "$@"
}

echo "--> Waiting for database and WordPress container to be ready..."
MAX_TRIES=30
COUNT=0
until docker compose exec -T db mariadb-admin ping -h localhost -u"${MYSQL_USER:-wp_user}" -p"${MYSQL_PASSWORD:-wp_secure_db_pass_2026}" --silent >/dev/null 2>&1; do
  COUNT=$((COUNT + 1))
  if [ $COUNT -gt $MAX_TRIES ]; then
    echo "ERROR: MariaDB failed to become ready in time."
    exit 1
  fi
  echo "Waiting for MariaDB ($COUNT/$MAX_TRIES)..."
  sleep 2
done
echo " MariaDB is responsive."

# Wait for WordPress files to be copied into place
COUNT=0
until wp core is-installed >/dev/null 2>&1 || wp core version >/dev/null 2>&1; do
  COUNT=$((COUNT + 1))
  if [ $COUNT -gt $MAX_TRIES ]; then
    echo "ERROR: WordPress core files not ready."
    exit 1
  fi
  echo "Waiting for WordPress files ($COUNT/$MAX_TRIES)..."
  sleep 2
done

# Check if WordPress is already installed
if wp core is-installed >/dev/null 2>&1; then
  echo " WordPress is already installed."
else
  echo "--> Installing WordPress Core..."
  wp core install \
    --url="${WP_SITE_URL}" \
    --title="${WP_SITE_TITLE}" \
    --admin_user="${WP_ADMIN_USER}" \
    --admin_password="${WP_ADMIN_PASSWORD}" \
    --admin_email="${WP_ADMIN_EMAIL}" \
    --skip-email
  echo " WordPress core successfully installed."
fi

echo "--> Configuring site options and permalinks..."
wp option update blogdescription "Evidence-Driven Healthcare Analytics & Digital Performance Intelligence"
wp option update timezone_string "${WP_TIMEZONE}"
wp option update default_pingback_flag 0
wp option update default_ping_status "closed"
wp option update default_comment_status "closed"
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard

echo "--> Installing & Activating Pinned Production & Dev Plugins..."
# Contact Form 7 pinned at 6.0.4
if ! wp plugin is-installed contact-form-7; then
  wp plugin install contact-form-7 --version=6.0.4 --activate
else
  wp plugin activate contact-form-7
fi

# Yoast SEO pinned at 24.5
if ! wp plugin is-installed wordpress-seo; then
  wp plugin install wordpress-seo --version=24.5 --activate
else
  wp plugin activate wordpress-seo
fi

# UpdraftPlus pinned at 1.26.11
if ! wp plugin is-installed updraftplus; then
  wp plugin install updraftplus --version=1.26.11 --activate
else
  wp plugin activate updraftplus
fi

# Query Monitor pinned at 4.0.7 (Dev Only)
if ! wp plugin is-installed query-monitor; then
  wp plugin install query-monitor --version=4.0.7 --activate
else
  wp plugin activate query-monitor
fi

# Activate Custom Child Theme if installed
if [ -d "wordpress/wp-content/themes/pharmainsights-child" ] && [ -f "wordpress/wp-content/themes/pharmainsights-child/style.css" ]; then
  echo "--> Activating pharmainsights-child theme..."
  wp theme activate pharmainsights-child || true
fi

# Activate Custom Dashboard Plugin if installed
if [ -d "wordpress/wp-content/plugins/pi-insights-dashboard" ] && [ -f "wordpress/wp-content/plugins/pi-insights-dashboard/pi-insights-dashboard.php" ]; then
  echo "--> Activating pi-insights-dashboard plugin..."
  wp plugin activate pi-insights-dashboard || true
fi

echo "--> Capturing Phase 1 Verification Artifacts..."
mkdir -p "${ROOT_DIR}/docs"
{
  echo "=== PharmaInsights Hub: Phase 1 Verification ==="
  echo "Generated: $(date -u)"
  echo ""
  echo "--- WordPress Core Version ---"
  wp core version
  echo ""
  echo "--- Installed & Active Plugins ---"
  wp plugin list
  echo ""
  echo "--- Active Theme ---"
  wp theme list --status=active
  echo ""
  echo "--- Site Settings Verification ---"
  echo "Site URL: $(wp option get siteurl)"
  echo "Home URL: $(wp option get home)"
  echo "Timezone: $(wp option get timezone_string)"
  echo "Permalink Structure: $(wp option get permalink_structure)"
} > "${ROOT_DIR}/docs/phase1_verification.txt"

echo "=========================================================="
echo " Setup Completed Successfully!"
echo " Verification saved to docs/phase1_verification.txt"
echo " Site URL: ${WP_SITE_URL}"
echo " Admin Panel: ${WP_SITE_URL}/wp-admin"
echo " Admin User: ${WP_ADMIN_USER}"
echo "=========================================================="
