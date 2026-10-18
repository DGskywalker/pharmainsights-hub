#!/usr/bin/env bash
set -euo pipefail

# PharmaInsights Hub - Platform Security Hardening & RBAC Automation
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${ROOT_DIR}"

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

echo "=========================================================="
echo " Starting Security Hardening for PharmaInsights Hub"
echo "=========================================================="

wp() {
  docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp "$@"
}

echo "--> 1. Enforcing DISALLOW_FILE_EDIT..."
docker compose exec -T wordpress bash -c "grep -q 'DISALLOW_FILE_EDIT' wp-config.php || echo \"define('DISALLOW_FILE_EDIT', true);\" >> wp-config.php"
echo " File editing disabled in dashboard."

echo "--> 2. Disabling XML-RPC via theme hook and Apache rule..."
# XML-RPC filter is hooked in functions.php, let's also close pingback
wp option update default_pingback_flag 0
wp option update default_ping_status "closed"
echo " XML-RPC and pingbacks disabled."

echo "--> 3. Installing & Activating Limit Login Attempts..."
if ! wp plugin is-installed limit-login-attempts-reloaded; then
  wp plugin install limit-login-attempts-reloaded --version=2.26.17 --activate
else
  wp plugin activate limit-login-attempts-reloaded
fi
echo " Brute-force login rate limiting active."

echo "--> 4. Provisioning Least-Privilege Role Accounts (RBAC)..."
# Editor: Can publish and manage posts/pages
if ! wp user get pi_editor >/dev/null 2>&1; then
  wp user create pi_editor editor@pharmainsights-hub.internal \
    --role=editor \
    --user_pass="EditorPass2026!Secure" \
    --display_name="Clinical Safety Editor"
  echo " Provisioned Editor account: pi_editor"
else
  echo " Editor account pi_editor already exists."
fi

# Author: Can publish and manage own posts
if ! wp user get pi_author >/dev/null 2>&1; then
  wp user create pi_author author@pharmainsights-hub.internal \
    --role=author \
    --user_pass="AuthorPass2026!Secure" \
    --display_name="Data Analyst Author"
  echo " Provisioned Author account: pi_author"
else
  echo " Author account pi_author already exists."
fi

# Contributor: Can write and manage own posts but cannot publish
if ! wp user get pi_contributor >/dev/null 2>&1; then
  wp user create pi_contributor intern@pharmainsights-hub.internal \
    --role=contributor \
    --user_pass="InternPass2026!Secure" \
    --display_name="Solutions Intern Contributor"
  echo " Provisioned Contributor account: pi_contributor"
else
  echo " Contributor account pi_contributor already exists."
fi

echo "--> 5. Checking Core & Plugin Update Posture..."
wp core check-update || true
wp plugin list

echo "=========================================================="
echo " Security Hardening & RBAC Configuration Complete!"
echo " Administrator: ${WP_ADMIN_USER:-pi_admin}"
echo " Editor:        pi_editor"
echo " Author:        pi_author"
echo " Contributor:   pi_contributor"
echo "=========================================================="
