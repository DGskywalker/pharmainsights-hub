#!/usr/bin/env bash
set -euo pipefail

# PharmaInsights Hub - Automated Backup Script
# Creates timestamped database dump and wp-content archive

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${ROOT_DIR}"

if [ -f .env ]; then
  export $(grep -v '^#' .env | xargs)
fi

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="${ROOT_DIR}/backups/backup_${TIMESTAMP}"
mkdir -p "${BACKUP_DIR}"

echo "=========================================================="
echo " Starting Platform Backup: ${TIMESTAMP}"
echo " Destination: ${BACKUP_DIR}"
echo "=========================================================="

echo "--> 1. Dumping MariaDB Database..."
docker compose exec -T db mariadb-dump \
  -u"${MYSQL_USER:-wp_user}" \
  -p"${MYSQL_PASSWORD:-wp_secure_db_pass_2026}" \
  "${MYSQL_DATABASE:-wordpress}" > "${BACKUP_DIR}/db_backup.sql"

DB_SIZE=$(ls -lh "${BACKUP_DIR}/db_backup.sql" | awk '{print $5}')
echo " Database dump completed (${DB_SIZE})."

echo "--> 2. Archiving wp-content (Themes, Custom Plugins, Uploads)..."
tar -czf "${BACKUP_DIR}/wp_content_backup.tar.gz" -C "${ROOT_DIR}/wordpress" wp-content
CONTENT_SIZE=$(ls -lh "${BACKUP_DIR}/wp_content_backup.tar.gz" | awk '{print $5}')
echo " wp-content archive completed (${CONTENT_SIZE})."

echo "--> 3. Writing Backup Manifest..."
CORE_VER=$(docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp core version 2>/dev/null || echo "6.7.2")
cat <<EOF > "${BACKUP_DIR}/backup_manifest.json"
{
  "timestamp": "${TIMESTAMP}",
  "created_at_utc": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "wordpress_version": "${CORE_VER}",
  "database_file": "db_backup.sql",
  "content_archive": "wp_content_backup.tar.gz",
  "environment": "Docker Compose Local Dev",
  "db_size": "${DB_SIZE}",
  "content_size": "${CONTENT_SIZE}"
}
EOF

echo "=========================================================="
echo " Backup Completed Successfully!"
echo " Manifest: ${BACKUP_DIR}/backup_manifest.json"
echo " Database: ${BACKUP_DIR}/db_backup.sql"
echo " Content:  ${BACKUP_DIR}/wp_content_backup.tar.gz"
echo "=========================================================="
