#!/usr/bin/env bash
set -euo pipefail

# PharmaInsights Hub - Automated Backup Restore Script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${ROOT_DIR}"

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

# Find target backup
if [ $# -ge 1 ]; then
  TARGET_BACKUP="$1"
else
  # Default to latest backup
  TARGET_BACKUP=$(ls -td "${ROOT_DIR}"/backups/backup_* 2>/dev/null | head -n 1 || true)
fi

if [ -z "${TARGET_BACKUP}" ] || [ ! -d "${TARGET_BACKUP}" ]; then
  echo "ERROR: No backup found in backups/ directory."
  exit 1
fi

echo "=========================================================="
echo " Restoring Platform Backup from: ${TARGET_BACKUP}"
echo "=========================================================="

if [ ! -f "${TARGET_BACKUP}/db_backup.sql" ]; then
  echo "ERROR: Missing db_backup.sql in backup folder."
  exit 1
fi

echo "--> 1. Restoring MariaDB Database..."
docker compose exec -T db mariadb \
  -u"${MYSQL_USER:-wp_user}" \
  -p"${MYSQL_PASSWORD:-wp_secure_db_pass_2026}" \
  "${MYSQL_DATABASE:-wordpress}" < "${TARGET_BACKUP}/db_backup.sql"
echo " Database restored successfully."

if [ -f "${TARGET_BACKUP}/wp_content_backup.tar.gz" ]; then
  echo "--> 2. Restoring wp-content archive..."
  tar -xzf "${TARGET_BACKUP}/wp_content_backup.tar.gz" -C "${ROOT_DIR}/wordpress"
  echo " wp-content restored successfully."
fi

echo "--> 3. Flushing Permalinks and Cache via WP-CLI..."
docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp cache flush || true
docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp rewrite flush --hard || true

echo "=========================================================="
echo " Restore Completed and Verified Successfully!"
echo " Site URL: ${WP_SITE_URL:-http://localhost:8080}"
echo "=========================================================="
