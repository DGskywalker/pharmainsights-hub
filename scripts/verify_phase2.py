#!/usr/bin/env python3
"""
PharmaInsights Hub - Phase 2 Verification & Link Integrity Checker
Validates:
1. All pages and articles return HTTP 200 OK.
2. Zero broken internal links.
3. Heading hierarchy integrity (H1 -> H2 -> H3, no skipped levels).
4. Alt text presence on all images.
5. Presence of mandatory medical disclaimers.
Saves complete audit output to docs/phase2_verification.txt.
"""

import urllib.request
import re
import sys
import os

BASE_URL = "http://localhost:8080"
ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DOCS_DIR = os.path.join(ROOT_DIR, "docs")
os.makedirs(DOCS_DIR, exist_ok=True)

PAGES_TO_CHECK = [
    "/",
    "/about/",
    "/insights/",
    "/drug-safety-explained/",
    "/digital-performance-dashboard/",
    "/contact/",
    "/privacy-policy/",
    "/accessibility-statement/"
]

ARTICLES_TO_CHECK = [
    "/what-is-pharmacovigilance/",
    "/how-to-read-disproportionality-signals/",
    "/what-faers-data-can-and-cannot-tell-you/",
    "/omnichannel-engagement-measuring-what-matters/",
    "/dashboard-design-principles-for-non-technical-audiences/",
    "/automating-recurring-reports-in-healthcare/",
    "/responsible-ai-in-healthcare-analytics/",
    "/why-data-quality-comes-first/"
]

report_lines = []
def log(msg=""):
    print(msg)
    report_lines.append(msg)

log("=== PharmaInsights Hub: Phase 2 Content & IA Verification Audit ===")
log(f"Target Site: {BASE_URL}")
log("------------------------------------------------------------------")

all_urls = PAGES_TO_CHECK + ARTICLES_TO_CHECK
visited_urls = set()
broken_links = []
alt_missing = []
heading_issues = []
disclaimer_missing = []

for path in all_urls:
    url = BASE_URL + path
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (Phase2-Verification-Audit)"})
        with urllib.request.urlopen(req) as response:
            status = response.getcode()
            html = response.read().decode("utf-8", errors="ignore")
            log(f" [HTTP {status}] {path}")

            # Check medical disclaimer on articles and drug safety page
            if path in ARTICLES_TO_CHECK or path == "/drug-safety-explained/":
                if "Not Medical Advice" not in html and "Educational Disclaimer" not in html:
                    disclaimer_missing.append(path)

            # Check heading hierarchy
            headings = re.findall(r'<h([1-6])[^>]*>', html, re.IGNORECASE)
            h_levels = [int(h) for h in headings]
            for i in range(len(h_levels) - 1):
                if h_levels[i+1] > h_levels[i] + 1:
                    heading_issues.append((path, f"Skipped heading level from H{h_levels[i]} to H{h_levels[i+1]}"))

            # Check images for alt attributes
            img_tags = re.findall(r'<img[^>]+>', html, re.IGNORECASE)
            for img in img_tags:
                if 'alt=""' in img or 'alt=' not in img:
                    alt_missing.append((path, img[:60]))

            # Find internal links to verify
            links = re.findall(r'href=[\'"]([^\'"]+)[\'"]', html)
            for link in links:
                if link.startswith("/") and not link.startswith("//") and not link.startswith("/wp-admin") and not link.startswith("/wp-json"):
                    clean_link = link.split("#")[0].split("?")[0]
                    if clean_link and clean_link not in visited_urls:
                        visited_urls.add(clean_link)
    except Exception as e:
        log(f" [FAIL] {path}: {e}")
        broken_links.append((path, str(e)))

log("\n--- Internal Link Resolution Check ---")
for link in sorted(visited_urls):
    link_url = BASE_URL + link
    try:
        req = urllib.request.Request(link_url, headers={"User-Agent": "Mozilla/5.0 (Phase2-LinkCheck)"})
        with urllib.request.urlopen(req) as resp:
            log(f" Valid link: {link} (HTTP {resp.getcode()})")
    except Exception as e:
        log(f" Broken internal link: {link} -> {e}")
        broken_links.append((link, str(e)))

log("\n--- Audit Summary Results ---")
log(f"Total Pages & Articles Checked: {len(all_urls)}")
log(f"Broken Links: {len(broken_links)}")
log(f"Missing Alt Text on Images: {len(alt_missing)}")
log(f"Skipped Heading Hierarchy Issues: {len(heading_issues)}")
log(f"Missing Educational Disclaimers: {len(disclaimer_missing)}")

if not broken_links and not alt_missing and not heading_issues and not disclaimer_missing:
    log("\n RESULT: Phase 2 Content & Information Architecture PASSED with 100% compliance!")
else:
    log("\n RESULT: Phase 2 Audit identified issues to review.")

with open(os.path.join(DOCS_DIR, "phase2_verification.txt"), "w") as f:
    f.write("\n".join(report_lines) + "\n")

print(f"\nAudit saved to {os.path.join(DOCS_DIR, 'phase2_verification.txt')}")
