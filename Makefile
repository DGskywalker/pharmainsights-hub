.PHONY: up down setup seed report test backup clean status help

help:
	@echo "PharmaInsights Hub - Automation Targets:"
	@echo "  make up       - Start Docker Compose stack (WordPress + MariaDB)"
	@echo "  make down     - Stop Docker Compose stack"
	@echo "  make setup    - Provision WordPress via WP-CLI (core, settings, plugins)"
	@echo "  make seed     - Generate synthetic omnichannel data and compute KPIs"
	@echo "  make report   - Generate automated weekly performance report (HTML + CSV)"
	@echo "  make test     - Run analytics & planted insights test suite (pytest)"
	@echo "  make backup   - Create timestamped database and wp-content backup"
	@echo "  make status   - Show container status and health"
	@echo "  make clean    - Remove local cache and temporary files"

up:
	docker compose up -d --remove-orphans

down:
	docker compose down

status:
	docker compose ps

setup:
	@bash scripts/setup_wordpress.sh

seed:
	@python3 analytics/generate_data.py
	@python3 analytics/kpis.py

report:
	@python3 analytics/weekly_report.py

test:
	@pytest analytics/tests/ -v

backup:
	@bash scripts/backup.sh

clean:
	rm -rf .pytest_cache __pycache__ */__pycache__
