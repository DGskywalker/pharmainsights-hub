<?php
/**
 * Plugin Name: PharmaInsights Omnichannel Dashboard
 * Plugin URI: http://localhost:8080/digital-performance-dashboard/
 * Description: Interactive life-sciences omnichannel performance analytics dashboard powered by local Chart.js and automated KPI modeling.
 * Version: 1.0.0
 * Author: PharmaInsights Engineering Team
 * Author URI: http://localhost:8080
 * License: GPL-2.0-or-later
 * Text Domain: pi-insights-dashboard
 *
 * @package PharmaInsights_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PI_DASHBOARD_VERSION', '1.0.0' );
define( 'PI_DASHBOARD_PATH', plugin_dir_path( __FILE__ ) );
define( 'PI_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register dashboard scripts and styles
 */
function pi_dashboard_register_assets() {
	wp_register_style(
		'pi-dashboard-css',
		PI_DASHBOARD_URL . 'assets/dashboard.css',
		array(),
		PI_DASHBOARD_VERSION
	);

	wp_register_script(
		'pi-chartjs',
		PI_DASHBOARD_URL . 'assets/chart.min.js',
		array(),
		'4.5.1',
		true
	);

	wp_register_script(
		'pi-dashboard-js',
		PI_DASHBOARD_URL . 'assets/dashboard.js',
		array( 'pi-chartjs' ),
		PI_DASHBOARD_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'pi_dashboard_register_assets' );

/**
 * Load KPI JSON data safely
 *
 * @return array Decoded KPI summary or empty array.
 */
function pi_dashboard_get_kpi_data() {
	$json_path = PI_DASHBOARD_PATH . 'data/kpi_summary.json';
	if ( ! file_exists( $json_path ) ) {
		return array();
	}

	$json_content = file_get_contents( $json_path );
	if ( empty( $json_content ) ) {
		return array();
	}

	$data = json_decode( $json_content, true );
	return is_array( $data ) ? $data : array();
}

/**
 * Handle CSV download via direct secure action
 */
function pi_dashboard_handle_csv_download() {
	if ( isset( $_GET['pi_action'] ) && 'download_csv' === $_GET['pi_action'] ) {
		check_admin_referer( 'pi_download_analytics_csv', 'pi_nonce' );

		$csv_path = PI_DASHBOARD_PATH . 'data/web_analytics.csv';
		if ( file_exists( $csv_path ) ) {
			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="pharmainsights_omnichannel_analytics_demo.csv"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			readfile( $csv_path );
			exit;
		}
	}
}
add_action( 'template_redirect', 'pi_dashboard_handle_csv_download' );

/**
 * Shortcode handler: [pi_dashboard]
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered dashboard HTML.
 */
function pi_dashboard_shortcode( $atts ) {
	$kpi_data = pi_dashboard_get_kpi_data();

	if ( empty( $kpi_data ) ) {
		return '<div class="pi-dashboard-notice error"><p>' . esc_html__( 'Analytics KPI data is currently initializing. Please run `make seed` from the root directory.', 'pi-insights-dashboard' ) . '</p></div>';
	}

	wp_enqueue_style( 'pi-dashboard-css' );
	wp_enqueue_script( 'pi-dashboard-js' );

	wp_localize_script(
		'pi-dashboard-js',
		'piDashboardData',
		array(
			'kpis' => $kpi_data,
		)
	);

	$summary = isset( $kpi_data['summary'] ) ? $kpi_data['summary'] : array();
	$csv_download_url = wp_nonce_url(
		add_query_arg( 'pi_action', 'download_csv', home_url( '/digital-performance-dashboard/' ) ),
		'pi_download_analytics_csv',
		'pi_nonce'
	);

	ob_start();
	?>
	<div class="pi-dashboard-wrapper" id="pi-dashboard">
		<!-- Header & Synthetic Notice -->
		<div class="pi-dashboard-header">
			<div class="pi-badge-container">
				<span class="pi-badge-synthetic"><?php esc_html_e( 'Synthetic Demo Data', 'pi-insights-dashboard' ); ?></span>
				<span class="pi-badge-period"><?php esc_html_e( '18-Month Lifecycle (May 2025 – Oct 2026)', 'pi-insights-dashboard' ); ?></span>
			</div>
			<div class="pi-header-actions">
				<a href="<?php echo esc_url( $csv_download_url ); ?>" class="pi-btn-download">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
					<?php esc_html_e( 'Download Raw CSV (33k+ Records)', 'pi-insights-dashboard' ); ?>
				</a>
			</div>
		</div>

		<!-- Executive KPI Scorecards -->
		<div class="pi-scorecard-grid">
			<div class="pi-card">
				<div class="pi-card-label"><?php esc_html_e( 'Total Active Sessions', 'pi-insights-dashboard' ); ?></div>
				<div class="pi-card-value"><?php echo esc_html( number_format( isset( $summary['total_sessions'] ) ? $summary['total_sessions'] : 0 ) ); ?></div>
				<div class="pi-card-sub text-positive">
					<?php echo esc_html( ( isset( $summary['wow_change_pct'] ) && $summary['wow_change_pct'] >= 0 ? '+' : '' ) . ( isset( $summary['wow_change_pct'] ) ? $summary['wow_change_pct'] : 0 ) . '% WoW' ); ?>
				</div>
			</div>

			<div class="pi-card">
				<div class="pi-card-label"><?php esc_html_e( 'Overall Conversion Rate', 'pi-insights-dashboard' ); ?></div>
				<div class="pi-card-value"><?php echo esc_html( ( isset( $summary['overall_conv_rate_pct'] ) ? $summary['overall_conv_rate_pct'] : 0 ) . '%' ); ?></div>
				<div class="pi-card-sub"><?php echo esc_html( number_format( isset( $summary['total_conversions'] ) ? $summary['total_conversions'] : 0 ) . ' Total Actions' ); ?></div>
			</div>

			<div class="pi-card">
				<div class="pi-card-label"><?php esc_html_e( 'Weighted Bounce Rate', 'pi-insights-dashboard' ); ?></div>
				<div class="pi-card-value"><?php echo esc_html( ( isset( $summary['overall_bounce_rate_pct'] ) ? $summary['overall_bounce_rate_pct'] : 0 ) . '%' ); ?></div>
				<div class="pi-card-sub"><?php esc_html_e( 'Across all devices & channels', 'pi-insights-dashboard' ); ?></div>
			</div>

			<div class="pi-card">
				<div class="pi-card-label"><?php esc_html_e( 'Avg Session Duration', 'pi-insights-dashboard' ); ?></div>
				<div class="pi-card-value"><?php echo esc_html( ( isset( $summary['avg_session_duration_sec'] ) ? round( $summary['avg_session_duration_sec'] / 60, 1 ) : 0 ) . ' min' ); ?></div>
				<div class="pi-card-sub"><?php echo esc_html( ( isset( $summary['avg_session_duration_sec'] ) ? $summary['avg_session_duration_sec'] : 0 ) . ' seconds' ); ?></div>
			</div>
		</div>

		<!-- Main Charts Grid -->
		<div class="pi-charts-grid">
			<!-- Chart 1: Monthly Sessions Trend -->
			<div class="pi-chart-panel span-2">
				<div class="pi-panel-header">
					<h2><?php esc_html_e( '18-Month Omnichannel Session Trajectory', 'pi-insights-dashboard' ); ?></h2>
					<span class="pi-panel-desc"><?php esc_html_e( 'Monthly volume aggregation illustrating macro growth and campaign surges', 'pi-insights-dashboard' ); ?></span>
				</div>
				<div class="pi-chart-container">
					<canvas id="piChartSessionsTrend" aria-label="<?php esc_attr_e( 'Line chart of 18-month session volume trajectory', 'pi-insights-dashboard' ); ?>" role="img"></canvas>
				</div>
				<div class="pi-insight-card">
					<strong><?php esc_html_e( 'What this tells us:', 'pi-insights-dashboard' ); ?></strong>
					<p><?php esc_html_e( 'Steady organic growth (+24%) over 18 months punctuated by prominent surges during October 2025 and June 2026 medical symposiums. A transient dip in March 2026 accurately reflects our simulated CMS migration anomaly.', 'pi-insights-dashboard' ); ?></p>
				</div>
			</div>

			<!-- Chart 2: Channel Mix -->
			<div class="pi-chart-panel">
				<div class="pi-panel-header">
					<h2><?php esc_html_e( 'Channel Traffic Mix', 'pi-insights-dashboard' ); ?></h2>
					<span class="pi-panel-desc"><?php esc_html_e( 'Distribution of total sessions by marketing channel', 'pi-insights-dashboard' ); ?></span>
				</div>
				<div class="pi-chart-container pie-container">
					<canvas id="piChartChannelMix" aria-label="<?php esc_attr_e( 'Doughnut chart of channel traffic mix', 'pi-insights-dashboard' ); ?>" role="img"></canvas>
				</div>
				<div class="pi-insight-card">
					<strong><?php esc_html_e( 'What this tells us:', 'pi-insights-dashboard' ); ?></strong>
					<p><?php esc_html_e( 'Organic Search (30.1%) and Paid Search (21.9%) deliver over half of initial reach. However, direct HCP Portal access has expanded from 6% to 11% share over the measured lifecycle.', 'pi-insights-dashboard' ); ?></p>
				</div>
			</div>

			<!-- Chart 3: Conversion Rate by Channel -->
			<div class="pi-chart-panel">
				<div class="pi-panel-header">
					<h2><?php esc_html_e( 'Conversion Rate by Channel (%)', 'pi-insights-dashboard' ); ?></h2>
					<span class="pi-panel-desc"><?php esc_html_e( 'High-value action rate (account signup / MSL inquiry)', 'pi-insights-dashboard' ); ?></span>
				</div>
				<div class="pi-chart-container">
					<canvas id="piChartConversionChannel" aria-label="<?php esc_attr_e( 'Bar chart comparing conversion rates across channels', 'pi-insights-dashboard' ); ?>" role="img"></canvas>
				</div>
				<div class="pi-insight-card">
					<strong><?php esc_html_e( 'What this tells us (Planted Insight):', 'pi-insights-dashboard' ); ?></strong>
					<p><?php esc_html_e( 'Email achieves the highest conversion efficiency (5.5%), almost triple Paid Search (2.2%). Opt-in medical communication workflows deliver substantially higher clinical stakeholder engagement.', 'pi-insights-dashboard' ); ?></p>
				</div>
			</div>

			<!-- Chart 4: Device Split & Bounce Rate -->
			<div class="pi-chart-panel">
				<div class="pi-panel-header">
					<h2><?php esc_html_e( 'Device Distribution & Bounce Gap', 'pi-insights-dashboard' ); ?></h2>
					<span class="pi-panel-desc"><?php esc_html_e( 'Traffic split vs. observed bounce rate by device', 'pi-insights-dashboard' ); ?></span>
				</div>
				<div class="pi-chart-container">
					<canvas id="piChartDeviceSplit" aria-label="<?php esc_attr_e( 'Bar chart of device bounce rates and sessions', 'pi-insights-dashboard' ); ?>" role="img"></canvas>
				</div>
				<div class="pi-insight-card">
					<strong><?php esc_html_e( 'What this tells us (Planted Insight):', 'pi-insights-dashboard' ); ?></strong>
					<p><?php esc_html_e( 'Mobile sessions experience a 62.4% bounce rate compared to 36.1% on desktop. Indicates a strategic opportunity to streamline mobile document viewers and dosing calculator touchpoints.', 'pi-insights-dashboard' ); ?></p>
				</div>
			</div>

			<!-- Chart 5: Top Content Categories -->
			<div class="pi-chart-panel">
				<div class="pi-panel-header">
					<h2><?php esc_html_e( 'Top Content Categories', 'pi-insights-dashboard' ); ?></h2>
					<span class="pi-panel-desc"><?php esc_html_e( 'Sessions and conversion volume by thematic section', 'pi-insights-dashboard' ); ?></span>
				</div>
				<div class="pi-chart-container">
					<canvas id="piChartTopContent" aria-label="<?php esc_attr_e( 'Horizontal bar chart of sessions by content category', 'pi-insights-dashboard' ); ?>" role="img"></canvas>
				</div>
				<div class="pi-insight-card">
					<strong><?php esc_html_e( 'What this tells us:', 'pi-insights-dashboard' ); ?></strong>
					<p><?php esc_html_e( 'Clinical Evidence and Dosing & Administration pages generate 52% of total engagement, confirming that healthcare practitioners prioritize technical clinical guidance over generalized brand materials.', 'pi-insights-dashboard' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Automated Anomaly Ledger Table -->
		<div class="pi-anomaly-section">
			<div class="pi-panel-header">
				<h2><?php esc_html_e( 'Statistical Anomaly Ledger (Z-Score Engine)', 'pi-insights-dashboard' ); ?></h2>
				<span class="pi-panel-desc"><?php esc_html_e( 'Automatically flagged events deviating by >2.0 standard deviations from 30-day baseline', 'pi-insights-dashboard' ); ?></span>
			</div>
			<div class="pi-table-responsive">
				<table class="pi-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date', 'pi-insights-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Classification', 'pi-insights-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Observed Sessions', 'pi-insights-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Expected (Rolling Mean)', 'pi-insights-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Z-Score', 'pi-insights-dashboard' ); ?></th>
							<th><?php esc_html_e( 'Root Cause & Analysis', 'pi-insights-dashboard' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$anomalies = isset( $kpi_data['anomalies'] ) ? $kpi_data['anomalies'] : array();
						if ( ! empty( $anomalies ) ) :
							foreach ( array_slice( $anomalies, 0, 6 ) as $item ) :
								$is_drop = ( strpos( $item['type'], 'Drop' ) !== false );
								?>
								<tr>
									<td><code><?php echo esc_html( $item['date'] ); ?></code></td>
									<td>
										<span class="pi-tag <?php echo $is_drop ? 'tag-warning' : 'tag-positive'; ?>">
											<?php echo esc_html( $item['type'] ); ?>
										</span>
									</td>
									<td><strong><?php echo esc_html( number_format( $item['observed_sessions'] ) ); ?></strong></td>
									<td><?php echo esc_html( number_format( $item['expected_sessions'] ) ); ?></td>
									<td><code><?php echo esc_html( $item['z_score'] ); ?></code></td>
									<td><?php echo esc_html( $item['description'] ); ?></td>
								</tr>
								<?php
							endforeach;
						else :
							?>
							<tr>
								<td colspan="6"><?php esc_html_e( 'No critical statistical anomalies currently flagged.', 'pi-insights-dashboard' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pi_dashboard', 'pi_dashboard_shortcode' );
