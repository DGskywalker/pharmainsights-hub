/**
 * PharmaInsights Hub - Omnichannel Analytics Dashboard JavaScript
 * Initializes 5 interactive Chart.js visualizations from pre-computed KPIs.
 */

document.addEventListener("DOMContentLoaded", function () {
    if (typeof piDashboardData === "undefined" || !piDashboardData.kpis) {
        console.warn("PharmaInsights Dashboard: No KPI data provided.");
        return;
    }

    const kpis = piDashboardData.kpis;
    const colors = {
        primary: "#0a2540",
        secondary: "#008080",
        accent: "#005a9c",
        amber: "#d97706",
        emerald: "#10b981",
        purple: "#7c3aed",
        slate: "#64748b"
    };

    Chart.defaults.font.family = "Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.color = "#475569";

    // 1. Line Chart: 18-Month Sessions Trend
    const ctxTrend = document.getElementById("piChartSessionsTrend");
    if (ctxTrend && kpis.monthly_trend) {
        new Chart(ctxTrend, {
            type: "line",
            data: {
                labels: kpis.monthly_trend.labels,
                datasets: [
                    {
                        label: "Total Sessions",
                        data: kpis.monthly_trend.sessions,
                        borderColor: colors.accent,
                        backgroundColor: "rgba(0, 90, 156, 0.08)",
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: colors.accent,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: "Conversions (x10 scale)",
                        data: kpis.monthly_trend.conversions.map(c => c * 10),
                        borderColor: colors.secondary,
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: { position: "top", labels: { boxWidth: 12, usePointStyle: true } }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: { color: "rgba(226, 232, 240, 0.6)" },
                        ticks: { callback: v => v.toLocaleString() }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart: Channel Mix
    const ctxMix = document.getElementById("piChartChannelMix");
    if (ctxMix && kpis.channel_kpis) {
        new Chart(ctxMix, {
            type: "doughnut",
            data: {
                labels: kpis.channel_kpis.labels,
                datasets: [{
                    data: kpis.channel_kpis.session_shares,
                    backgroundColor: [
                        colors.primary,
                        colors.accent,
                        colors.secondary,
                        colors.amber,
                        colors.emerald,
                        colors.purple,
                        colors.slate
                    ],
                    borderWidth: 2,
                    borderColor: "#ffffff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: "right", labels: { boxWidth: 12, padding: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ` ${context.label}: ${context.parsed}% share`;
                            }
                        }
                    }
                },
                cutout: "62%"
            }
        });
    }

    // 3. Bar Chart: Conversion Rate by Channel
    const ctxConv = document.getElementById("piChartConversionChannel");
    if (ctxConv && kpis.channel_kpis) {
        // Highlight Email with Emerald Green, others Navy
        const bgColors = kpis.channel_kpis.labels.map(l => (l === "Email" ? colors.emerald : colors.accent));

        new Chart(ctxConv, {
            type: "bar",
            data: {
                labels: kpis.channel_kpis.labels,
                datasets: [{
                    label: "Conversion Rate (%)",
                    data: kpis.channel_kpis.conv_rates,
                    backgroundColor: bgColors,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Conversion Rate: ${ctx.parsed.y}%`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => v + "%" },
                        grid: { color: "rgba(226, 232, 240, 0.6)" }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 4. Bar Chart: Device Bounce Rate
    const ctxDevice = document.getElementById("piChartDeviceSplit");
    if (ctxDevice && kpis.device_kpis) {
        new Chart(ctxDevice, {
            type: "bar",
            data: {
                labels: kpis.device_kpis.labels,
                datasets: [
                    {
                        label: "Bounce Rate (%)",
                        data: kpis.device_kpis.bounce_rates,
                        backgroundColor: [colors.accent, colors.amber, colors.slate],
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` Bounce Rate: ${ctx.parsed.y}%`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => v + "%" },
                        grid: { color: "rgba(226, 232, 240, 0.6)" }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 5. Horizontal Bar Chart: Top Content Categories
    const ctxContent = document.getElementById("piChartTopContent");
    if (ctxContent && kpis.category_kpis) {
        new Chart(ctxContent, {
            type: "bar",
            data: {
                labels: kpis.category_kpis.labels,
                datasets: [{
                    label: "Sessions",
                    data: kpis.category_kpis.sessions,
                    backgroundColor: colors.secondary,
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: "y",
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.x.toLocaleString()} Sessions`
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { callback: v => v.toLocaleString() },
                        grid: { color: "rgba(226, 232, 240, 0.6)" }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }
});
