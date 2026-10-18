#!/usr/bin/env python3
"""
Generate high-resolution PNG featured images for PharmaInsights Hub articles
using Matplotlib, and attach them with descriptive alt text via WP-CLI.
"""

import os
import subprocess
import matplotlib.pyplot as plt
import matplotlib.patches as patches

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
ASSETS_DIR = os.path.join(ROOT_DIR, "wordpress", "content", "assets")
os.makedirs(ASSETS_DIR, exist_ok=True)

def create_image(filename, title, subtitle, boxes):
    fig, ax = plt.subplots(figsize=(10, 5.625), dpi=100) # 1000x562.5 (16:9)
    fig.patch.set_facecolor('#0a2540')
    ax.set_facecolor('#0a2540')
    ax.set_xlim(0, 100)
    ax.set_ylim(0, 100)
    ax.axis('off')

    # Outer border card
    outer = patches.FancyBboxPatch((4, 4), 92, 92, boxstyle="round,pad=1",
                                  facecolor='#0f345c', edgecolor='#008080', linewidth=2)
    ax.add_patch(outer)

    # Title & Subtitle
    ax.text(50, 88, title, color='#ffffff', fontsize=18, fontweight='bold', ha='center', va='center')
    ax.text(50, 81, subtitle, color='#94a3b8', fontsize=11, ha='center', va='center')

    # Content boxes
    n = len(boxes)
    width = 82 / n - 2
    for i, (b_title, b_lines, color) in enumerate(boxes):
        x = 9 + i * (width + 2)
        box = patches.FancyBboxPatch((x, 22), width, 50, boxstyle="round,pad=0.8",
                                     facecolor='#1e293b', edgecolor=color, linewidth=2)
        ax.add_patch(box)
        ax.text(x + width/2, 65, b_title, color=color, fontsize=12, fontweight='bold', ha='center')
        for j, line in enumerate(b_lines):
            ax.text(x + width/2, 55 - j*7, line, color='#cbd5e1', fontsize=9.5, ha='center')

    # Footer note
    ax.text(50, 10, "PharmaInsights Hub Educational Graphic • Synthetic Demo Environment",
            color='#64748b', fontsize=9, ha='center')

    filepath = os.path.join(ASSETS_DIR, filename)
    plt.tight_layout()
    plt.savefig(filepath, facecolor=fig.get_facecolor(), edgecolor='none', bbox_inches='tight')
    plt.close()
    print(f"Created image: {filename}")

IMAGES = [
    (
        "pv-signal-detection.png",
        "Pharmacovigilance Signal Detection Workflow",
        "From Spontaneous Reports to Multidisciplinary Causality Assessment",
        [
            ("1. Ingestion", ["Spontaneous ICSRs", "Clinical Trials", "MedDRA Coding"], "#60a5fa"),
            ("2. Data Mining", ["PRR & ROR Screening", "Bayesian EBGM", "Disproportionality"], "#2dd4bf"),
            ("3. Clinical Review", ["Biological Plausibility", "Temporal Latency", "Benefit-Risk Action"], "#facc15")
        ]
    ),
    (
        "disproportionality-analysis.png",
        "Disproportionality Analysis: 2x2 Contingency Modeling",
        "Understanding Statistical Signal Extraction Without Causal Fallacies",
        [
            ("Contingency Matrix", ["Target Drug + Event (a)", "Target Drug + Other (b)", "Other Drugs + Event (c)"], "#38bdf8"),
            ("Equations", ["PRR = [a/(a+b)]/[c/(c+d)]", "ROR = (a*d) / (b*c)", "Evans: PRR>=2, Chi>=4"], "#2dd4bf"),
            ("Fallacy Guardrails", ["PRR != Incidence Rate", "Confounding by Indication", "Reporting Biases"], "#f87171")
        ]
    ),
    (
        "faers-methodology.png",
        "FAERS Spontaneous Reporting Methodology",
        "Balancing Post-Marketing Data Utility with Methodological Realities",
        [
            ("Analytical Strengths", ["Ultra-Rare Event Detection", "Real Polypharmacy Data", "Quarterly Open Access"], "#34d399"),
            ("Key Limitations", ["No Exposure Denominator", "Underreporting (Weber)", "Unverified Causality"], "#f87171"),
            ("Analyst Best Practices", ["Frame as Reporting Rate", "Pair with Longitudinal RWE", "Clean Duplicate Cases"], "#60a5fa")
        ]
    ),
    (
        "omnichannel-funnel.png",
        "Healthcare Omnichannel Performance Funnel",
        "Measuring Content Depth & Professional Engagement Beyond Vanity Traffic",
        [
            ("1. Qualified Reach", ["Organic Search Queries", "Targeted HCP Referrals", "Medical Information"], "#60a5fa"),
            ("2. Interaction Depth", ["Clinical Study Dwell Time", "Reconstitution Tool Usage", "Scroll Depth Past Dosages"], "#2dd4bf"),
            ("3. High-Value Action", ["HCP Portal Registrations", "MSL Consultation Requests", "Resource Downloads"], "#facc15")
        ]
    ),
    (
        "dashboard-ux-hierarchy.png",
        "Healthcare Dashboard Information Architecture",
        "Guiding Executive & Medical Stakeholders from Headlines to Actions",
        [
            ("1. Executive Summary", ["Top 4 Strategic KPIs", "Week-over-Week Changes", "Status Trajectories"], "#38bdf8"),
            ("2. Visual Analytics", ["Channel Trajectories", "Interactive Device Split", "Statistical Anomalies"], "#2dd4bf"),
            ("3. Action Narrative", ["Plain Language Takeaways", "Probable Root Causes", "2-3 Concrete Next Steps"], "#facc15")
        ]
    ),
    (
        "reporting-automation-pipeline.png",
        "Automated Reporting Engine Pipeline",
        "Eliminating Manual Spreadsheets with Python & Scheduled Workflows",
        [
            ("1. Ingestion", ["Immutable Event Logs", "Automated Daily Feeds", "Strict Data Taxonomies"], "#60a5fa"),
            ("2. Calculation", ["Standardized KPIs", "Rolling Baseline Z-Scores", "Outlier Flagging"], "#2dd4bf"),
            ("3. Delivery", ["HTML & CSV Generation", "Zero-Touch Scheduling", "Full Audit Traceability"], "#4ade80")
        ]
    ),
    (
        "responsible-ai-guardrails.png",
        "Responsible AI & Clinical Governance Guardrails",
        "Deploying LLM Intelligence Safely in Regulated Life-Sciences Environments",
        [
            ("Refusal Guardrails", ["Strict Rejection of Medical", "Diagnosis & Treatment", "Safe Authority Referrals"], "#f87171"),
            ("Context Grounding", ["Retrieval Augmented (RAG)", "Bound to Site Articles", "Zero Hallucination Tolerance"], "#38bdf8"),
            ("Architecture Privacy", ["Server-Side API Proxy", "Zero PII Transmitted", "Transparent Offline Mode"], "#4ade80")
        ]
    ),
    (
        "data-governance-lifecycle.png",
        "Healthcare Data Quality & Governance Lifecycle",
        "Engineering Complete Data Trustworthiness Across Omnichannel Platforms",
        [
            ("1. Standardization", ["Strict UTM Taxonomies", "Unified Schema Defs", "Form Validation Checks"], "#38bdf8"),
            ("2. Verification", ["Automated Unit Tests", "KPI Bound Constraints", "Planted Pattern Verifications"], "#2dd4bf"),
            ("3. Lineage & Audit", ["Transparent Data Lineage", "Reproducible Notebooks", "Compliance Readiness"], "#4ade80")
        ]
    )
]

print("--> Generating PNG featured images...")
for filename, title, subtitle, boxes in IMAGES:
    create_image(filename, title, subtitle, boxes)

# Attach to published articles via WP-CLI
ARTICLE_MAP = {
    "pv-signal-detection.png": "what-is-pharmacovigilance",
    "disproportionality-analysis.png": "how-to-read-disproportionality-signals",
    "faers-methodology.png": "what-faers-data-can-and-cannot-tell-you",
    "omnichannel-funnel.png": "omnichannel-engagement-measuring-what-matters",
    "dashboard-ux-hierarchy.png": "dashboard-design-principles-for-non-technical-audiences",
    "reporting-automation-pipeline.png": "automating-recurring-reports-in-healthcare",
    "responsible-ai-guardrails.png": "responsible-ai-in-healthcare-analytics",
    "data-governance-lifecycle.png": "why-data-quality-comes-first"
}

def run_wp(args):
    cmd = [
        "docker", "compose", "run", "--rm", "--no-TTY",
        "-e", "WP_CLI_CACHE_DIR=/tmp/.wp-cli",
        "wpcli", "wp"
    ] + args
    res = subprocess.run(cmd, cwd=ROOT_DIR, capture_output=True, text=True)
    return res.stdout.strip()

print("--> Uploading PNGs to WordPress Media Library and setting featured images...")
for img_name, post_slug in ARTICLE_MAP.items():
    container_path = f"/var/www/html/wp-content/content/assets/{img_name}"
    title = img_name.replace(".png", "").replace("-", " ").title()
    media_id = run_wp([
        "media", "import", container_path,
        f"--title={title}",
        f"--alt={title} conceptual diagram for life sciences analytics",
        "--porcelain"
    ])
    if media_id and media_id.isdigit():
        post_id = run_wp(["post", "list", f"--name={post_slug}", "--field=ID"])
        if post_id and post_id.isdigit():
            run_wp(["post", "meta", "set", post_id, "_thumbnail_id", media_id])
            print(f" Linked {img_name} (Media {media_id}) to Post {post_slug} (ID {post_id})")

# Also assign menu locations now that after_setup_theme registered them
print("--> Assigning Primary and Footer Menus to registered locations...")
run_wp(["menu", "location", "assign", "Primary Menu", "primary"])
run_wp(["menu", "location", "assign", "Footer Menu", "footer"])
print(" All featured images and menu locations successfully configured!")
