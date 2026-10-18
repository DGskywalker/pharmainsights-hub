#!/usr/bin/env python3
"""
PharmaInsights Hub - Omnichannel KPI Calculation & Anomaly Detection Engine
Computes:
  - High-level executive scorecards (Sessions, Users, Conversions, Bounce Rate)
  - Monthly & Weekly trajectories
  - Channel mix & conversion performance
  - Device performance & bounce discrepancies
  - Content category ranking
  - Statistical anomaly detection (Z-scores on rolling baseline)
Exports: analytics/data/kpi_summary.json & plugin data directory.
"""

import os
import json
import numpy as np
import pandas as pd

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DATA_DIR = os.path.join(ROOT_DIR, "analytics", "data")
CSV_PATH = os.path.join(DATA_DIR, "web_analytics.csv")
JSON_OUTPUT = os.path.join(DATA_DIR, "kpi_summary.json")
PLUGIN_DATA_DIR = os.path.join(ROOT_DIR, "wordpress", "wp-content", "plugins", "pi-insights-dashboard", "data")
os.makedirs(PLUGIN_DATA_DIR, exist_ok=True)
PLUGIN_JSON_OUTPUT = os.path.join(PLUGIN_DATA_DIR, "kpi_summary.json")

def calculate_kpis(csv_path=CSV_PATH):
    if not os.path.exists(csv_path):
        raise FileNotFoundError(f"Analytics dataset not found at {csv_path}. Run generate_data.py first.")

    df = pd.read_csv(csv_path)
    df["date"] = pd.to_datetime(df["date"])
    df["year_month"] = df["date"].dt.to_period("M").astype(str)
    df["year_week"] = df["date"].dt.to_period("W").astype(str)

    # 1. Executive Summary
    total_sessions = int(df["sessions"].sum())
    total_users = int(df["users"].sum())
    total_pageviews = int(df["pageviews"].sum())
    total_conversions = int(df["conversions"].sum())
    overall_conv_rate = round(float(total_conversions / total_sessions * 100), 2)
    overall_bounce_rate = round(float((df["bounce_rate"] * df["sessions"]).sum() / total_sessions * 100), 2)
    avg_session_duration = round(float((df["avg_session_duration_sec"] * df["sessions"]).sum() / total_sessions), 1)

    # 2. Monthly Trend (Line Chart Data)
    monthly_df = df.groupby("year_month").agg({
        "sessions": "sum",
        "users": "sum",
        "conversions": "sum"
    }).reset_index()
    monthly_df["conv_rate"] = (monthly_df["conversions"] / monthly_df["sessions"] * 100).round(2)
    monthly_trend = {
        "labels": monthly_df["year_month"].tolist(),
        "sessions": monthly_df["sessions"].tolist(),
        "users": monthly_df["users"].tolist(),
        "conversions": monthly_df["conversions"].tolist(),
        "conv_rate": monthly_df["conv_rate"].tolist()
    }

    # 3. Channel Performance & Mix (Donut + Bar Charts)
    channel_df = df.groupby("channel").agg({
        "sessions": "sum",
        "users": "sum",
        "conversions": "sum"
    }).reset_index()
    channel_df["session_share"] = (channel_df["sessions"] / total_sessions * 100).round(2)
    channel_df["conv_rate"] = (channel_df["conversions"] / channel_df["sessions"] * 100).round(2)
    channel_df = channel_df.sort_values(by="sessions", ascending=False)

    channel_kpis = {
        "labels": channel_df["channel"].tolist(),
        "sessions": channel_df["sessions"].tolist(),
        "session_shares": channel_df["session_share"].tolist(),
        "conversions": channel_df["conversions"].tolist(),
        "conv_rates": channel_df["conv_rate"].tolist()
    }

    # 4. Device Performance (Doughnut Chart)
    device_df = df.groupby("device").agg({
        "sessions": "sum",
        "conversions": "sum"
    }).reset_index()
    device_df["session_share"] = (device_df["sessions"] / total_sessions * 100).round(2)
    device_df["conv_rate"] = (device_df["conversions"] / device_df["sessions"] * 100).round(2)
    # Weighted bounce rate
    device_bounce = df.groupby("device").apply(
        lambda x: (x["bounce_rate"] * x["sessions"]).sum() / x["sessions"].sum() * 100
    ).round(2).to_dict()
    device_df["bounce_rate"] = device_df["device"].map(device_bounce)

    device_kpis = {
        "labels": device_df["device"].tolist(),
        "sessions": device_df["sessions"].tolist(),
        "session_shares": device_df["session_share"].tolist(),
        "conv_rates": device_df["conv_rate"].tolist(),
        "bounce_rates": [device_bounce[d] for d in device_df["device"]]
    }

    # 5. Page Category Performance (Horizontal Bar Chart)
    cat_df = df.groupby("page_category").agg({
        "sessions": "sum",
        "pageviews": "sum",
        "conversions": "sum"
    }).reset_index()
    cat_df["session_share"] = (cat_df["sessions"] / total_sessions * 100).round(2)
    cat_df["conv_rate"] = (cat_df["conversions"] / cat_df["sessions"] * 100).round(2)
    cat_df = cat_df.sort_values(by="sessions", ascending=False)

    category_kpis = {
        "labels": cat_df["page_category"].tolist(),
        "sessions": cat_df["sessions"].tolist(),
        "pageviews": cat_df["pageviews"].tolist(),
        "conversions": cat_df["conversions"].tolist(),
        "conv_rates": cat_df["conv_rate"].tolist()
    }

    # 6. Country Breakdown
    country_df = df.groupby("country").agg({"sessions": "sum", "conversions": "sum"}).reset_index()
    country_df["session_share"] = (country_df["sessions"] / total_sessions * 100).round(2)
    country_df = country_df.sort_values(by="sessions", ascending=False)
    country_kpis = {
        "labels": country_df["country"].tolist(),
        "sessions": country_df["sessions"].tolist(),
        "shares": country_df["session_share"].tolist()
    }

    # 7. Recent Week-over-Week & Month-over-Month Changes
    weekly_agg = df.groupby("year_week")["sessions"].sum().tolist()
    latest_week = weekly_agg[-1] if len(weekly_agg) > 0 else 0
    prev_week = weekly_agg[-2] if len(weekly_agg) > 1 else latest_week
    wow_change_pct = round(((latest_week - prev_week) / prev_week * 100), 2) if prev_week > 0 else 0.0

    latest_month = monthly_df["sessions"].iloc[-1] if len(monthly_df) > 0 else 0
    prev_month = monthly_df["sessions"].iloc[-2] if len(monthly_df) > 1 else latest_month
    mom_change_pct = round(((latest_month - prev_month) / prev_month * 100), 2) if prev_month > 0 else 0.0

    # 8. Anomaly Detection (Daily Z-scores over 30-day Rolling Mean)
    daily_totals = df.groupby("date")["sessions"].sum().reset_index().sort_values("date")
    daily_totals["rolling_mean"] = daily_totals["sessions"].rolling(window=21, min_periods=7).mean()
    daily_totals["rolling_std"] = daily_totals["sessions"].rolling(window=21, min_periods=7).std().replace(0, 1)
    daily_totals["z_score"] = (daily_totals["sessions"] - daily_totals["rolling_mean"]) / daily_totals["rolling_std"]

    anomalies = []
    # Drop anomalies: z-score < -1.85
    drop_events = daily_totals[daily_totals["z_score"] < -1.85]
    for _, row in drop_events.iterrows():
        anomalies.append({
            "date": row["date"].strftime("%Y-%m-%d"),
            "type": "Traffic Drop Anomaly",
            "observed_sessions": int(row["sessions"]),
            "expected_sessions": int(row["rolling_mean"]),
            "z_score": round(float(row["z_score"]), 2),
            "description": "Statistically significant traffic depression following simulated site migration (Days 320-334)."
        })

    # Spike anomalies: z-score > 2.2
    spike_events = daily_totals[daily_totals["z_score"] > 2.2]
    for _, row in spike_events.iterrows():
        anomalies.append({
            "date": row["date"].strftime("%Y-%m-%d"),
            "type": "Campaign Surge",
            "observed_sessions": int(row["sessions"]),
            "expected_sessions": int(row["rolling_mean"]),
            "z_score": round(float(row["z_score"]), 2),
            "description": "Statistically significant engagement surge corresponding to medical symposium campaigns."
        })

    # Summary payload
    kpi_payload = {
        "generated_at": pd.Timestamp.now("UTC").isoformat(),
        "data_disclaimer": "Synthetic demo data for educational analytics demonstration only.",
        "summary": {
            "total_sessions": total_sessions,
            "total_users": total_users,
            "total_pageviews": total_pageviews,
            "total_conversions": total_conversions,
            "overall_conv_rate_pct": overall_conv_rate,
            "overall_bounce_rate_pct": overall_bounce_rate,
            "avg_session_duration_sec": avg_session_duration,
            "wow_change_pct": wow_change_pct,
            "mom_change_pct": mom_change_pct
        },
        "monthly_trend": monthly_trend,
        "channel_kpis": channel_kpis,
        "device_kpis": device_kpis,
        "category_kpis": category_kpis,
        "country_kpis": country_kpis,
        "anomalies": anomalies
    }

    # Write JSON files
    with open(JSON_OUTPUT, "w") as f:
        json.dump(kpi_payload, f, indent=2)

    with open(PLUGIN_JSON_OUTPUT, "w") as f:
        json.dump(kpi_payload, f, indent=2)

    print(f" KPIs successfully calculated and exported:")
    print(f" -> {JSON_OUTPUT}")
    print(f" -> {PLUGIN_JSON_OUTPUT}")
    print(f" Total Sessions: {total_sessions:,} | Conversions: {total_conversions:,} ({overall_conv_rate}%)")
    print(f" Detected Anomalies: {len(anomalies)} events flagged (Z-score threshold)")
    return kpi_payload

if __name__ == "__main__":
    calculate_kpis()
