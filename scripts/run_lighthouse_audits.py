#!/usr/bin/env python3
"""
PharmaInsights Hub - Lighthouse Audit Automation
Executes real Lighthouse audits across Desktop and Mobile configurations for:
  1. Home Page (/)
  2. Educational Article (/what-is-pharmacovigilance/)
  3. Interactive Dashboard (/digital-performance-dashboard/)
Saves full JSON reports and summarizes real scores in docs/lighthouse_summary.txt.
"""

import os
import subprocess
import json

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DOCS_DIR = os.path.join(ROOT_DIR, "docs")
os.makedirs(DOCS_DIR, exist_ok=True)

CHROME_BIN = "/Applications/Brave Browser.app/Contents/MacOS/Brave Browser"
BASE_URL = "http://localhost:8080"

TARGETS = [
    ("Home", "/", "home"),
    ("Article", "/what-is-pharmacovigilance/", "article"),
    ("Dashboard", "/digital-performance-dashboard/", "dashboard")
]

summary_lines = [
    "=== PharmaInsights Hub: Official Real Lighthouse Audit Results ===",
    f"Base URL: {BASE_URL}",
    "Browser: Brave / Chromium (Headless)",
    "------------------------------------------------------------------"
]

print("--> Starting Real Lighthouse Audits...")

for name, path, key in TARGETS:
    url = BASE_URL + path
    for mode in ["desktop", "mobile"]:
        output_file = os.path.join(DOCS_DIR, f"lighthouse_{key}_{mode}.json")
        cmd = [
            "npx", "--yes", "lighthouse", url,
            "--no-enable-error-reporting",
            "--chrome-flags=--headless --no-sandbox",
            f"--output=json",
            f"--output-path={output_file}",
            "--quiet"
        ]
        if mode == "desktop":
            cmd.append("--preset=desktop")

        env = os.environ.copy()
        env["CHROME_PATH"] = CHROME_BIN

        print(f" Auditing {name} ({mode.title()}) at {url}...")
        res = subprocess.run(cmd, env=env, capture_output=True, text=True)

        if os.path.exists(output_file):
            try:
                with open(output_file) as f:
                    data = json.load(f)
                cats = data.get("categories", {})
                perf = int(cats.get("performance", {}).get("score", 0) * 100)
                a11y = int(cats.get("accessibility", {}).get("score", 0) * 100)
                bp = int(cats.get("best-practices", {}).get("score", 0) * 100)
                seo = int(cats.get("seo", {}).get("score", 0) * 100)
                
                result_str = f"{name} ({mode.title()}): Performance: {perf} | Accessibility: {a11y} | Best Practices: {bp} | SEO: {seo}"
                print(f"    -> {result_str}")
                summary_lines.append(result_str)
            except Exception as e:
                print(f"    Failed parsing report for {name} ({mode}): {e}")
        else:
            print(f"    Lighthouse failed for {name} ({mode}): {res.stderr}")

with open(os.path.join(DOCS_DIR, "lighthouse_summary.txt"), "w") as f:
    f.write("\n".join(summary_lines) + "\n")

print(f"\n Summary written to {os.path.join(DOCS_DIR, 'lighthouse_summary.txt')}")
