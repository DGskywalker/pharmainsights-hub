# PharmaInsights Hub: Platform Administrator Checklist

**Target:** Digital Solutions Interns, System Administrators, and Omnichannel Leads  
**Purpose:** Standard operating procedures for recurring weekly, monthly, and quarterly administrative cadences.  

---

## 1. Weekly Maintenance Cadence (Every Monday, 09:00 GST)

| Check | Task Description | Execution Command / Action | Responsible |
| :--- | :--- | :--- | :--- |
| [ ] | **Automated Report Generation** | Run `make report` to produce weekly HTML & CSV summaries. | Data Analyst |
| [ ] | **Anomaly Triaging** | Inspect `weekly_report_latest.html` for any Z-score alerts (&gt;2.0 or &lt;-1.85). | Omnichannel Lead |
| [ ] | **Automated Platform Backup** | Run `make backup` to ensure timestamped SQL and media snapshots exist. | System Admin |
| [ ] | **Link & Content Integrity** | Run `.venv/bin/python scripts/verify_phase2.py` to confirm zero 404 broken links. | Content Lead |
| [ ] | **Contact Form Functional Test** | Submit a test message on `/contact/` and verify delivery in DB logs. | Digital Intern |
| [ ] | **Test Suite Regression Check** | Run `make test` to ensure mathematical invariants remain unbroken. | Solutions Engineer |

---

## 2. Monthly Maintenance Cadence (First Tuesday of Each Month)

| Check | Task Description | Execution Command / Action | Responsible |
| :--- | :--- | :--- | :--- |
| [ ] | **Plugin & Core Update Audit** | `docker compose run --rm --no-TTY wpcli wp plugin list --update=available` | System Admin |
| [ ] | **Disaster Recovery Drill** | Execute `bash scripts/restore.sh` on an isolated test volume to verify full restoration. | System Admin |
| [ ] | **Lighthouse & A11y Audit** | Run `.venv/bin/python scripts/run_lighthouse_audits.py` and review against 90+ targets. | Front-End Lead |
| [ ] | **Role & User Access Audit** | Review active user list with `wp user list` and remove inactive credentials. | Lead Administrator |
| [ ] | **Database Optimization** | `docker compose run --rm --no-TTY wpcli wp db optimize` | Database Admin |
| [ ] | **Disk Space & Volume Pruning** | Inspect Docker volume sizes: `docker system df`. | System Admin |

---

## 3. Quarterly Governance & Strategy Review

| Check | Task Description | Execution Command / Action | Responsible |
| :--- | :--- | :--- | :--- |
| [ ] | **Taxonomy & UTM Review** | Ensure Google Analytics / internal logging conventions match `CONTENT_STYLE_GUIDE.md`. | Strategy Lead |
| [ ] | **Synthetic Model Calibration** | Review seasonality factors in `generate_data.py` to reflect new clinical milestones. | Data Analyst |
| [ ] | **Security Hardening Re-Check** | Run `bash scripts/harden.sh` to ensure rate limiting and XML-RPC blocks remain active. | Security Officer |
| [ ] | **Editorial Disclaimer Audit** | Sample 20 random published articles to ensure "Not Medical Advice" notice is present. | Compliance Officer |
