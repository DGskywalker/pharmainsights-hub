"""
Pytest Suite for PharmaInsights Hub Omnichannel Analytics & Planted Insights
Validates mathematical consistency of KPIs and ensures all 4 planted insights
are accurately detected by the analytics engine.
"""

import os
import pytest
import pandas as pd
from analytics.generate_data import generate_omnichannel_data, OUTPUT_CSV
from analytics.kpis import calculate_kpis

@pytest.fixture(scope="session")
def analytics_data():
    """Generates synthetic dataset and computes KPIs for the test session."""
    if not os.path.exists(OUTPUT_CSV):
        generate_omnichannel_data()
    kpi_summary = calculate_kpis()
    df = pd.read_csv(OUTPUT_CSV)
    return {"df": df, "kpi": kpi_summary}

# ---------------------------------------------------------------------------
# 1. MATHEMATICAL CONSISTENCY TESTS
# ---------------------------------------------------------------------------

def test_summary_kpi_math(analytics_data):
    summary = analytics_data["kpi"]["summary"]
    assert summary["total_sessions"] > 0
    assert summary["total_users"] > 0
    assert summary["total_users"] <= summary["total_sessions"]
    assert summary["total_conversions"] > 0
    assert 0.0 < summary["overall_conv_rate_pct"] < 100.0
    assert 0.0 < summary["overall_bounce_rate_pct"] < 100.0
    assert summary["avg_session_duration_sec"] > 0

def test_channel_shares_sum_to_100(analytics_data):
    channel_kpis = analytics_data["kpi"]["channel_kpis"]
    total_share = sum(channel_kpis["session_shares"])
    assert pytest.approx(total_share, 0.5) == 100.0

def test_device_shares_sum_to_100(analytics_data):
    device_kpis = analytics_data["kpi"]["device_kpis"]
    total_share = sum(device_kpis["session_shares"])
    assert pytest.approx(total_share, 0.5) == 100.0

# ---------------------------------------------------------------------------
# 2. PLANTED INSIGHT 1: EMAIL HIGHEST CONVERSION RATE
# ---------------------------------------------------------------------------

def test_planted_insight_email_highest_conversion(analytics_data):
    channel_kpis = analytics_data["kpi"]["channel_kpis"]
    conv_dict = dict(zip(channel_kpis["labels"], channel_kpis["conv_rates"]))
    
    assert "Email" in conv_dict
    email_rate = conv_dict["Email"]
    
    for ch, rate in conv_dict.items():
        if ch != "Email":
            assert email_rate > rate, f"Email conv rate ({email_rate}%) must exceed {ch} ({rate}%)"

# ---------------------------------------------------------------------------
# 3. PLANTED INSIGHT 2: MOBILE HIGHER BOUNCE RATE THAN DESKTOP
# ---------------------------------------------------------------------------

def test_planted_insight_mobile_bounce_exceeds_desktop(analytics_data):
    device_kpis = analytics_data["kpi"]["device_kpis"]
    bounce_dict = dict(zip(device_kpis["labels"], device_kpis["bounce_rates"]))
    
    assert "Mobile" in bounce_dict and "Desktop" in bounce_dict
    mobile_bounce = bounce_dict["Mobile"]
    desktop_bounce = bounce_dict["Desktop"]
    
    # Mobile should be markedly higher (at least 15 percentage points higher)
    assert mobile_bounce > desktop_bounce + 15.0, (
        f"Mobile bounce ({mobile_bounce}%) must be >15% higher than Desktop ({desktop_bounce}%)"
    )

# ---------------------------------------------------------------------------
# 4. PLANTED INSIGHT 3: TRAFFIC DROP ANOMALY DETECTION
# ---------------------------------------------------------------------------

def test_planted_insight_migration_traffic_drop_detected(analytics_data):
    anomalies = analytics_data["kpi"]["anomalies"]
    drop_anomalies = [a for a in anomalies if a["type"] == "Traffic Drop Anomaly"]
    
    assert len(drop_anomalies) > 0, "Anomaly detection engine must identify at least one drop anomaly"
    
    # Check that at least one flagged drop occurs during the simulated migration period (March 2026)
    march_2026_drops = [a for a in drop_anomalies if "2026-03" in a["date"]]
    assert len(march_2026_drops) > 0, "Anomaly engine must flag traffic drop in March 2026 (Days 320-334)"

# ---------------------------------------------------------------------------
# 5. PLANTED INSIGHT 4: HCP PORTAL SUSTAINED GROWTH
# ---------------------------------------------------------------------------

def test_planted_insight_hcp_portal_compound_growth(analytics_data):
    df = analytics_data["df"]
    df["date"] = pd.to_datetime(df["date"])
    
    hcp_df = df[df["channel"] == "HCP Portal"].sort_values("date")
    
    # Compare first 90 days vs last 90 days
    start_cutoff = hcp_df["date"].min() + pd.Timedelta(days=90)
    end_cutoff = hcp_df["date"].max() - pd.Timedelta(days=90)
    
    q1_sessions = hcp_df[hcp_df["date"] <= start_cutoff]["sessions"].sum()
    q6_sessions = hcp_df[hcp_df["date"] >= end_cutoff]["sessions"].sum()
    
    growth_ratio = q6_sessions / q1_sessions
    assert growth_ratio > 1.5, f"HCP Portal sessions must show >50% growth (observed {growth_ratio:.2f}x)"
