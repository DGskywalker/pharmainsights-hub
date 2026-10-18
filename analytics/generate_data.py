#!/usr/bin/env python3
"""
PharmaInsights Hub - Synthetic Omnichannel Web Analytics Data Generator
Generates an 18-month daily synthetic dataset reflecting life-sciences digital performance.
Planted Statistical Insights:
  1. Email achieves the highest conversion rate across all channels (~5.4% vs ~1.8% average).
  2. Mobile experiences a significantly higher bounce rate than Desktop (~62% vs ~36%).
  3. A temporary traffic anomaly (drop) occurs following a simulated site migration in Month 11 (Days 320-334).
  4. HCP Portal exhibits sustained compound growth (+180% over 18 months).
"""

import os
import numpy as np
import pandas as pd
from datetime import datetime, timedelta

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DATA_DIR = os.path.join(ROOT_DIR, "analytics", "data")
os.makedirs(DATA_DIR, exist_ok=True)
OUTPUT_CSV = os.path.join(DATA_DIR, "web_analytics.csv")

def generate_omnichannel_data():
    np.random.seed(42)

    # 18-month date range: May 1, 2025 to October 18, 2026 (536 days)
    start_date = datetime(2025, 5, 1)
    end_date = datetime(2026, 10, 18)
    num_days = (end_date - start_date).days + 1
    dates = [start_date + timedelta(days=i) for i in range(num_days)]

    channels = ["Email", "Organic Search", "Paid Search", "Social", "Direct", "Referral", "HCP Portal"]
    devices = ["Desktop", "Mobile", "Tablet"]
    countries = ["AE", "SA", "KW", "QA", "EG", "GB", "US"]
    page_categories = [
        "Pharmacovigilance",
        "Clinical Evidence",
        "Dosing & Administration",
        "Product Monograph",
        "Patient Resources"
    ]

    # Baseline daily volume weights
    channel_weights = {
        "Organic Search": 0.30,
        "Paid Search": 0.22,
        "Direct": 0.16,
        "Email": 0.12,
        "HCP Portal": 0.08,  # Starts smaller, grows steadily
        "Referral": 0.07,
        "Social": 0.05
    }
    device_weights = {"Desktop": 0.52, "Mobile": 0.40, "Tablet": 0.08}
    country_weights = {"AE": 0.28, "SA": 0.25, "KW": 0.12, "QA": 0.10, "EG": 0.10, "GB": 0.08, "US": 0.07}
    category_weights = {
        "Clinical Evidence": 0.28,
        "Dosing & Administration": 0.24,
        "Pharmacovigilance": 0.20,
        "Product Monograph": 0.16,
        "Patient Resources": 0.12
    }

    records = []

    for day_idx, current_date in enumerate(dates):
        # Day of week seasonality: Monday-Thursday higher, weekends lower
        dow = current_date.weekday()
        weekday_factor = 1.15 if dow < 4 else (0.85 if dow == 4 else 0.70)

        # General seasonal trend: gentle growth over 18 months
        macro_growth = 1.0 + (day_idx / num_days) * 0.25

        # Planted Insight 4: HCP Portal growth multiplier (ramps from 0.7x to 2.0x)
        hcp_growth = 0.7 + (day_idx / num_days) * 1.35

        # Planted Insight 3: Simulated Site Migration issue (Days 320 to 334: March 17-31, 2026)
        migration_penalty = 0.20 if (320 <= day_idx <= 334) else 1.0

        # Marketing campaign spikes:
        # Spike 1: Q4 Medical Congress (Day 170 to 175)
        # Spike 2: Annual Safety Symposium (Day 410 to 415)
        campaign_boost = 1.0
        if 170 <= day_idx <= 175:
            campaign_boost = 1.45
        elif 410 <= day_idx <= 415:
            campaign_boost = 1.60

        date_str = current_date.strftime("%Y-%m-%d")

        for ch in channels:
            for dev in devices:
                # To maintain realistic sample size, sample 2-3 primary countries per channel/device slice daily
                sampled_countries = np.random.choice(countries, size=3, replace=False, p=list(country_weights.values()))
                for ctry in sampled_countries:
                    cat = np.random.choice(page_categories, p=list(category_weights.values()))

                    base_sessions = 60 * channel_weights[ch] * device_weights[dev] * country_weights[ctry] * 20
                    multiplier = weekday_factor * macro_growth * campaign_boost

                    if ch == "HCP Portal":
                        multiplier *= hcp_growth
                    if ch in ["Organic Search", "Paid Search", "Direct", "Referral"] and migration_penalty < 1.0:
                        multiplier *= migration_penalty

                    # Add stochastic noise
                    noise = np.random.normal(1.0, 0.12)
                    sessions = max(1, int(base_sessions * multiplier * noise))
                    users = max(1, int(sessions * np.random.uniform(0.78, 0.92)))
                    pageviews = int(sessions * np.random.uniform(1.8, 3.4))

                    # Planted Insight 2: Mobile higher bounce rate
                    if dev == "Mobile":
                        bounce_rate = np.clip(np.random.normal(0.63, 0.05), 0.45, 0.85)
                        avg_duration = max(25, int(np.random.normal(75, 18)))
                    elif dev == "Desktop":
                        bounce_rate = np.clip(np.random.normal(0.36, 0.04), 0.20, 0.55)
                        avg_duration = max(45, int(np.random.normal(185, 35)))
                    else:  # Tablet
                        bounce_rate = np.clip(np.random.normal(0.44, 0.05), 0.25, 0.65)
                        avg_duration = max(35, int(np.random.normal(135, 25)))

                    # Planted Insight 1: Email has the highest conversion rate
                    if ch == "Email":
                        conv_rate = np.clip(np.random.normal(0.056, 0.009), 0.035, 0.085)
                    elif ch == "HCP Portal":
                        conv_rate = np.clip(np.random.normal(0.038, 0.007), 0.020, 0.060)
                    elif ch == "Referral":
                        conv_rate = np.clip(np.random.normal(0.026, 0.005), 0.012, 0.045)
                    elif ch == "Paid Search":
                        conv_rate = np.clip(np.random.normal(0.022, 0.004), 0.010, 0.038)
                    elif ch == "Organic Search":
                        conv_rate = np.clip(np.random.normal(0.019, 0.004), 0.008, 0.032)
                    elif ch == "Direct":
                        conv_rate = np.clip(np.random.normal(0.017, 0.003), 0.006, 0.028)
                    else:  # Social
                        conv_rate = np.clip(np.random.normal(0.011, 0.003), 0.003, 0.022)

                    # Device impact on conversion: Desktop converts slightly higher
                    if dev == "Desktop":
                        conv_rate *= 1.15
                    elif dev == "Mobile":
                        conv_rate *= 0.82

                    conversions = int(np.round(sessions * conv_rate))

                    records.append({
                        "date": date_str,
                        "channel": ch,
                        "device": dev,
                        "country": ctry,
                        "page_category": cat,
                        "sessions": sessions,
                        "users": users,
                        "pageviews": pageviews,
                        "bounce_rate": round(float(bounce_rate), 4),
                        "avg_session_duration_sec": int(avg_duration),
                        "conversions": conversions
                    })

    df = pd.DataFrame(records)
    df.to_csv(OUTPUT_CSV, index=False)
    print(f" Synthetic omnichannel dataset successfully generated!")
    print(f" File: {OUTPUT_CSV}")
    print(f" Total Rows: {len(df):,}")
    print(f" Date Range: {df['date'].min()} to {df['date'].max()} ({num_days} days)")
    print(f" Total Sessions: {df['sessions'].sum():,}")
    print(f" Total Conversions: {df['conversions'].sum():,}")
    return df

if __name__ == "__main__":
    generate_omnichannel_data()
