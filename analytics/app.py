"""
PharmaInsights Hub - Streamlit Omnichannel Intelligence Explorer
Local interactive exploration tool for data analysts and strategy teams.
Run with: .venv/bin/streamlit run analytics/app.py
"""

import os
import streamlit as st
import pandas as pd
import numpy as np

st.set_page_config(
    page_title="PharmaInsights Hub | Omnichannel Explorer",
    page_icon="📊",
    layout="wide"
)

ROOT_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CSV_PATH = os.path.join(ROOT_DIR, "analytics", "data", "web_analytics.csv")

@st.cache_data
def load_data():
    if not os.path.exists(CSV_PATH):
        st.error(f"Dataset not found at {CSV_PATH}. Please run `make seed` first.")
        st.stop()
    df = pd.read_csv(CSV_PATH)
    df["date"] = pd.to_datetime(df["date"])
    return df

df = load_data()

# Header & Disclaimer
st.title("PharmaInsights Hub: Omnichannel Analytics Explorer")
st.warning("⚠️ **Notice: Synthetic Demo Data** — All metrics and dimensions are synthetically generated for educational demonstration. Content does not constitute clinical or medical advice.")

# Sidebar Filters
st.sidebar.header("Filter & Segment Dimensions")
min_date = df["date"].min().date()
max_date = df["date"].max().date()
date_range = st.sidebar.date_input("Date Range", [min_date, max_date], min_value=min_date, max_value=max_date)

all_channels = sorted(df["channel"].unique().tolist())
selected_channels = st.sidebar.multiselect("Channels", all_channels, default=all_channels)

all_devices = sorted(df["device"].unique().tolist())
selected_devices = st.sidebar.multiselect("Devices", all_devices, default=all_devices)

all_countries = sorted(df["country"].unique().tolist())
selected_countries = st.sidebar.multiselect("Countries", all_countries, default=all_countries)

# Apply filters
mask = (
    (df["channel"].isin(selected_channels)) &
    (df["device"].isin(selected_devices)) &
    (df["country"].isin(selected_countries))
)
if len(date_range) == 2:
    start_d, end_d = date_range
    mask &= (df["date"].dt.date >= start_d) & (df["date"].dt.date <= end_d)

filtered_df = df[mask]

# Top KPI Scorecards
col1, col2, col3, col4 = st.columns(4)
total_sessions = int(filtered_df["sessions"].sum())
total_conversions = int(filtered_df["conversions"].sum())
conv_rate = (total_conversions / total_sessions * 100) if total_sessions > 0 else 0
weighted_bounce = ((filtered_df["bounce_rate"] * filtered_df["sessions"]).sum() / total_sessions * 100) if total_sessions > 0 else 0

col1.metric("Total Sessions", f"{total_sessions:,}")
col2.metric("Total Conversions", f"{total_conversions:,}")
col3.metric("Conversion Rate", f"{conv_rate:.2f}%")
col4.metric("Weighted Bounce Rate", f"{weighted_bounce:.2f}%")

st.markdown("---")

# Visualizations Row 1
r1_c1, r1_c2 = st.columns([2, 1])

with r1_c1:
    st.subheader("Daily Sessions Trajectory")
    daily_trend = filtered_df.groupby("date")["sessions"].sum().reset_index()
    st.line_chart(daily_trend.set_index("date")["sessions"])

with r1_c2:
    st.subheader("Channel Volume Share")
    channel_mix = filtered_df.groupby("channel")["sessions"].sum().reset_index()
    st.bar_chart(channel_mix.set_index("channel")["sessions"])

# Visualizations Row 2
r2_c1, r2_c2 = st.columns(2)

with r2_c1:
    st.subheader("Conversion Rate by Channel (%)")
    ch_conv = filtered_df.groupby("channel").agg({"sessions": "sum", "conversions": "sum"}).reset_index()
    ch_conv["conv_rate"] = (ch_conv["conversions"] / ch_conv["sessions"] * 100).round(2)
    st.bar_chart(ch_conv.set_index("channel")["conv_rate"])

with r2_c2:
    st.subheader("Bounce Rate by Device (%)")
    dev_b = filtered_df.groupby("device").apply(
        lambda x: (x["bounce_rate"] * x["sessions"]).sum() / x["sessions"].sum() * 100
    ).round(2).reset_index()
    dev_b.columns = ["device", "bounce_rate"]
    st.bar_chart(dev_b.set_index("device")["bounce_rate"])

# Raw Data Inspector
with st.expander("Inspect Raw Filtered Dataset"):
    st.dataframe(filtered_df.head(200))
    csv_data = filtered_df.to_csv(index=False).encode("utf-8")
    st.download_button(
        "Download Filtered CSV",
        data=csv_data,
        file_name="pharmainsights_filtered_analytics.csv",
        mime="text/csv"
    )
