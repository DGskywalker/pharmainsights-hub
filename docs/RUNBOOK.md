# PharmaInsights Hub: Platform Operations Runbook

**Audience:** Platform Administrators, Solutions Engineers, and Omnichannel Strategy Leads  
**Environment:** Local Containerized (Docker Compose + WordPress 6.7 + MariaDB 11.4)  
**Timezone:** Asia/Dubai (UTC+4)  

---

## 1. Quick Start & Lifecycle Management

All platform operations are standardized via the root `Makefile`:

```bash
# Start all platform containers in the background
make up

# View live container status and health
make status

# Stop all platform services
make down

# Seed the synthetic omnichannel dataset and recalculate KPIs
make seed

# Run the automated weekly analytics report
make report

# Execute automated test suite (KPI math & planted insights)
make test

# Generate a timestamped backup of the database and wp-content
make backup
```

---

## 2. Platform Architecture & Service Ports

| Service Name | Container Name | Internal Port | Host Port | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| **WordPress** | `pharmainsights_wordpress` | 80 | **8080** | Apache + PHP 8.2 runtime |
| **MariaDB** | `pharmainsights_db` | 3306 | **3307** | Persistent database |
| **WP-CLI** | `pharmainsights_wpcli` | - | - | On-demand management container |

---

## 3. User Management & Role-Based Access Control (RBAC)

PharmaInsights Hub enforces the principle of least privilege:

| Role | Username | Sample Credentials | Capabilities |
| :--- | :--- | :--- | :--- |
| **Administrator** | `pi_admin` | In `.env` (`WP_ADMIN_PASSWORD`) | Full system control, updates, plugins, settings |
| **Editor** | `pi_editor` | `EditorPass2026!Secure` | Publish, edit, and schedule any post or page |
| **Author** | `pi_author` | `AuthorPass2026!Secure` | Write, publish, and edit own articles only |
| **Contributor** | `pi_contributor` | `InternPass2026!Secure` | Draft and edit own posts; cannot publish |

### How to Add a New User via WP-CLI
```bash
docker compose run --rm --no-TTY -e WP_CLI_CACHE_DIR=/tmp/.wp-cli wpcli wp user create \
  <username> <email> \
  --role=<editor|author|contributor> \
  --user_pass="<SecurePassword>" \
  --display_name="<Full Name>"
```

---

## 4. Content Publishing & Scheduling Workflow

### Publishing a New Educational Article
1. Log in to `http://localhost:8080/wp-admin` as `pi_editor` or `pi_author`.
2. Navigate to **Posts > Add New Post**.
3. Draft the title and content ensuring compliance with `docs/CONTENT_STYLE_GUIDE.md`:
   - Mandatory disclaimer: Insert block pattern `pharmainsights/disclaimer-notice`.
   - Call to action: Insert block pattern `pharmainsights/cta-omnichannel`.
   - Assign Category: `Pharmacovigilance`, `Data Analytics`, `Digital Health`, or `AI and Automation`.
   - Assign 2–4 relevant tags.
   - Upload and select a featured 16:9 diagram (PNG/SVG) and write descriptive Alt Text.
   - Enter Yoast SEO meta description.
4. **Immediate Publish:** Click **Publish** > Confirm.
5. **Scheduled Publishing:**
   - In the Post settings sidebar, click **Publish Immediately** > Select date/time in the future (optimized for Tuesday or Thursday 08:00 Asia/Dubai time).
   - Click **Schedule**.

---

## 5. Backup & Disaster Recovery Procedures

### Running an Automated Backup
```bash
make backup
# Or execute directly:
bash scripts/backup.sh
```
Backups are archived into `backups/backup_YYYYMMDD_HHMMSS/` containing:
- `db_backup.sql`: Full MariaDB database dump.
- `wp_content_backup.tar.gz`: Compressed theme, custom plugins, and media uploads.
- `backup_manifest.json`: Verification checksum and version metadata.

### Performing a Test Restore
To restore from the latest available backup:
```bash
bash scripts/restore.sh
```
To restore from a specific previous backup archive:
```bash
bash scripts/restore.sh backups/backup_20261018_104932
```

---

## 6. Security Hardening & Maintenance Cadence

To apply or audit security configurations:
```bash
bash scripts/harden.sh
```
Hardening measures enforced:
- `DISALLOW_FILE_EDIT=true`: Blocks web-based theme/plugin code modification.
- XML-RPC and pingbacks disabled to eliminate amplification attacks.
- Brute-force rate limiting enforced via `limit-login-attempts-reloaded`.
- DB root password isolated in `.env` and omitted from version control.

---

## 7. Troubleshooting Common Incidents

### Incident 1: Port Collision on Host Port 8080 or 3307
- **Symptom:** Docker fails with `bind: address already in use`.
- **Fix:** Update `WP_PORT` or `DB_PORT` in `.env` (e.g. `WP_PORT=8081`, `DB_PORT=3308`) and run `make down && make up`.

### Incident 2: Anomaly Not Triggering in Weekly Report
- **Symptom:** `make report` shows 0 anomalies detected.
- **Cause:** Raw dataset not seeded or corrupted.
- **Fix:** Execute `make seed` to re-generate the 18-month baseline and re-run `make test`.

### Incident 3: Missing KPI Data in WordPress Dashboard
- **Symptom:** Dashboard displays initialization warning notice.
- **Fix:** Run `make seed`, which automatically copies `kpi_summary.json` to `wordpress/wp-content/plugins/pi-insights-dashboard/data/kpi_summary.json`.
