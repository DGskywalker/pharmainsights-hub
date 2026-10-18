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

// =========================================================================
// PHASE 6: SAFE AI INSIGHTS ASSISTANT (SERVER-SIDE PROXY & OFFLINE ENGINE)
// =========================================================================

/**
 * Register REST API route for Ask the Insights Assistant
 */
function pi_assistant_register_routes() {
	register_rest_route(
		'pi-insights/v1',
		'/chat',
		array(
			'methods'             => 'POST',
			'callback'            => 'pi_assistant_handle_chat',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'pi_assistant_register_routes' );

/**
 * REST API Chat Callback with safety guardrails and rate limiting
 *
 * @param WP_REST_Request $request REST request object.
 * @return WP_REST_Response Response object.
 */
function pi_assistant_handle_chat( WP_REST_Request $request ) {
	$question = sanitize_text_field( $request->get_param( 'question' ) );
	if ( empty( $question ) ) {
		return new WP_REST_Response( array( 'error' => 'Please provide a valid question.' ), 400 );
	}

	// 1. Rate Limiting via IP hash (Max 15 requests per 60 seconds)
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
	$ip_hash = 'pi_rate_' . md5( $ip . wp_salt() );
	$req_count = (int) get_transient( $ip_hash );
	if ( $req_count >= 15 ) {
		return new WP_REST_Response(
			array(
				'error' => 'Rate limit exceeded (maximum 15 inquiries per minute). Please pause before submitting another question.',
			),
			429
		);
	}
	set_transient( $ip_hash, $req_count + 1, 60 );

	// 2. Deterministic Medical Advice Refusal Barrier
	$medical_keywords = array( 'diagnos', 'symptom', 'prescri', 'cure', 'should i take', 'my dose', 'my pain', 'treat my', 'am i sick', 'disease', 'illness', 'doctor' );
	$lower_q = strtolower( $question );
	foreach ( $medical_keywords as $keyword ) {
		if ( strpos( $lower_q, $keyword ) !== false ) {
			return new WP_REST_Response(
				array(
					'mode'     => 'refusal_guardrail',
					'status'   => 'refused',
					'answer'   => "⚠️ **Medical Advice Refusal:** I am an educational data and analytics assistant for PharmaInsights Hub. I am strictly programmed to refuse requests for personal medical advice, clinical diagnosis, or therapeutic recommendations.\n\nFor clinical guidance or adverse event reporting, please consult a licensed physician or official health authority.",
					'sources'  => array( 'Platform Safety Guardrails (WCAG / Compliance Policy)' ),
				),
				200
			);
		}
	}

	// 3. Check for Server-Side API Key in Environment
	$api_key = getenv( 'OPENAI_API_KEY' );
	if ( empty( $api_key ) && isset( $_ENV['OPENAI_API_KEY'] ) ) {
		$api_key = $_ENV['OPENAI_API_KEY'];
	}

	// If API Key is present, proxy to LLM; otherwise use deterministic offline engine
	if ( ! empty( $api_key ) && 'sk-placeholder' !== $api_key ) {
		$response_data = pi_assistant_call_llm_proxy( $question, $api_key );
	} else {
		$response_data = pi_assistant_offline_engine( $question );
	}

	return new WP_REST_Response( $response_data, 200 );
}

/**
 * Deterministic offline domain grounding engine
 *
 * @param string $question Sanitized user question.
 * @return array Answer payload.
 */
function pi_assistant_offline_engine( $question ) {
	$q = strtolower( $question );
	$kpi_data = pi_dashboard_get_kpi_data();
	$summary = isset( $kpi_data['summary'] ) ? $kpi_data['summary'] : array();

	if ( strpos( $q, 'email' ) !== false || strpos( $q, 'convert' ) !== false || strpos( $q, 'best channel' ) !== false || strpos( $q, 'highest conversion' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "In our 18-month synthetic omnichannel dataset, **Email** achieved the highest conversion rate at **5.5%** (generating 720+ high-value actions from ~13,100 sessions). By comparison, Paid Search converted at **2.2%** and Organic Search at **1.9%**.\n\n**Strategic Takeaway:** Opt-in clinical education workflows and targeted medical newsletters demonstrate significantly higher user intent than broad top-of-funnel search acquisition.",
			'sources' => array(
				'Article: Omnichannel Engagement: Measuring What Matters (/omnichannel-engagement-measuring-what-matters/)',
				'Dataset: 18-Month Synthetic Web Analytics (web_analytics.csv)'
			),
		);
	}

	if ( strpos( $q, 'mobile' ) !== false || strpos( $q, 'device' ) !== false || strpos( $q, 'bounce' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "**Mobile sessions experience an average bounce rate of 62.4%**, compared to **36.1% on Desktop** (a 26.3 percentage point discrepancy). Tablet sessions averaged 44.2%.\n\n**Strategic Takeaway:** This planted performance insight indicates that healthcare professionals accessing technical clinical monographs on mobile devices encounter layout friction. Optimizing mobile document viewers and condensing dosing guides into collapsible accordions will improve mobile retention.",
			'sources' => array(
				'Article: Dashboard Design Principles for Non-Technical Audiences (/dashboard-design-principles-for-non-technical-audiences/)',
				'Digital Performance Dashboard (/digital-performance-dashboard/)'
			),
		);
	}

	if ( strpos( $q, 'prr' ) !== false || strpos( $q, 'ror' ) !== false || strpos( $q, 'disproportionality' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "**PRR (Proportional Reporting Ratio)** and **ROR (Reporting Odds Ratio)** are statistical disproportionality metrics used in spontaneous adverse event surveillance.\n\n- **PRR Formula:** `[a / (a + b)] / [c / (c + d)]`\n- **ROR Formula:** `(a * d) / (b * c)`\n\n**Critical Caveat:** Disproportionality is an *exploratory signal detection tool*, not a measure of epidemiological incidence. Spontaneous databases (such as FAERS) lack an exposure denominator, meaning a PRR of 3.0 does not mean a patient has a threefold higher risk of the event.",
			'sources' => array(
				'Article: How to Read a Disproportionality Signal Without Over-Interpreting It (/how-to-read-disproportionality-signals/)',
				'Drug Safety Explained (/drug-safety-explained/)'
			),
		);
	}

	if ( strpos( $q, 'faers' ) !== false || strpos( $q, 'fda' ) !== false || strpos( $q, 'limitation' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "The **FDA Adverse Event Reporting System (FAERS)** provides valuable real-world surveillance for rare adverse events and polypharmacy interactions, but carries 4 strict methodological boundaries:\n\n1. **No Exposure Denominator:** Total patient exposure is unknown; incidence cannot be computed.\n2. **Underreporting:** Captures <10% of real adverse events (Weber effect).\n3. **Unverified Causality:** Reports reflect temporal occurrences, not verified pharmacological causation.\n4. **Duplicate Entries:** Multiple submissions for a single clinical incident distorts counts without deduplication.",
			'sources' => array(
				'Article: What FAERS Data Can and Cannot Tell You (/what-faers-data-can-and-cannot-tell-you/)'
			),
		);
	}

	if ( strpos( $q, 'anomal' ) !== false || strpos( $q, 'march' ) !== false || strpos( $q, 'drop' ) !== false || strpos( $q, 'migration' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "Our anomaly detection engine flags statistical deviations exceeding 2.0 standard deviations (Z-score). In **March 2026 (Days 320–334)**, an intentional traffic anomaly was detected where daily sessions dipped to ~230 (Z-score -1.86 to -2.1).\n\n**Root Cause:** This reflects a simulated CMS migration event where canonical indexing issues temporarily depressed organic search discovery before recovery in early April.",
			'sources' => array(
				'Article: Automating Recurring Reports in Regulated Healthcare (/automating-recurring-reports-in-healthcare/)',
				'Automated Anomaly Ledger (/digital-performance-dashboard/)'
			),
		);
	}

	if ( strpos( $q, 'hcp' ) !== false || strpos( $q, 'portal' ) !== false || strpos( $q, 'growth' ) !== false ) {
		return array(
			'mode'    => 'offline_demo_grounded',
			'answer'  => "The **HCP Portal** channel exhibited steady compound growth (+180% over 18 months), scaling from ~150 sessions/day in May 2025 to over 650 sessions/day by October 2026.\n\n**Strategic Takeaway:** Direct physician portal adoption expanded its total traffic share from 6% to 11%, demonstrating successful ongoing engagement among verified healthcare providers.",
			'sources' => array(
				'Digital Performance Dashboard (/digital-performance-dashboard/)',
				'Weekly Performance Briefing (analytics/data/weekly_report_latest.html)'
			),
		);
	}

	// Default fallback response
	return array(
		'mode'    => 'offline_demo_grounded',
		'answer'  => "Welcome to the **PharmaInsights Assistant** (Offline Demonstration Mode). I can answer questions grounded in our 8 educational articles and synthetic 18-month omnichannel dataset.\n\n**Try asking:**\n- *Which marketing channel has the highest conversion rate?*\n- *Why do mobile users experience higher bounce rates?*\n- *How is PRR calculated in pharmacovigilance?*\n- *What are the primary limitations of FAERS data?*\n- *What caused the traffic anomaly in March 2026?*",
		'sources' => array(
			'PharmaInsights Hub Knowledge Base (8 Educational Articles)',
			'18-Month Synthetic Dataset (364k+ Sessions)'
		),
	);
}

/**
 * Server-side proxy for external LLM API (if OPENAI_API_KEY is configured)
 *
 * @param string $question User prompt.
 * @param string $api_key API key.
 * @return array Formatted response.
 */
function pi_assistant_call_llm_proxy( $question, $api_key ) {
	$endpoint = 'https://api.openai.com/v1/chat/completions';
	$system_prompt = "You are the PharmaInsights Hub AI Assistant, an educational life-sciences data intelligence advisor. You are strictly forbidden from offering personal medical advice, clinical diagnoses, or therapeutic recommendations. Answers must be strictly grounded in healthcare omnichannel analytics, pharmacovigilance methodology (PRR, ROR, FAERS), and our 18-month synthetic dataset. Maintain a professional, objective tone.";

	$body = array(
		'model'       => 'gpt-4o-mini',
		'messages'    => array(
			array( 'role' => 'system', 'content' => $system_prompt ),
			array( 'role' => 'user', 'content' => $question ),
		),
		'temperature' => 0.2,
		'max_tokens'  => 400,
	);

	$response = wp_remote_post(
		$endpoint,
		array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return pi_assistant_offline_engine( $question );
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( isset( $data['choices'][0]['message']['content'] ) ) {
		return array(
			'mode'    => 'live_llm_proxy',
			'answer'  => $data['choices'][0]['message']['content'],
			'sources' => array( 'Server-Side OpenAI Proxy (Grounded in PharmaInsights Content)' ),
		);
	}

	return pi_assistant_offline_engine( $question );
}

/**
 * Shortcode [pi_assistant] to render the AI Insights Assistant UI
 */
function pi_assistant_shortcode() {
	$api_key = getenv( 'OPENAI_API_KEY' );
	$is_live = ( ! empty( $api_key ) && 'sk-placeholder' !== $api_key );

	ob_start();
	?>
	<div class="pi-assistant-container" id="pi-assistant-app">
		<div class="pi-assistant-badge-bar">
			<span class="pi-badge-synthetic"><?php echo $is_live ? esc_html__( 'Live AI Mode (Server Proxy)', 'pi-insights-dashboard' ) : esc_html__( 'Offline Demo Mode (Grounding Simulation)', 'pi-insights-dashboard' ); ?></span>
			<span class="pi-badge-period"><?php esc_html_e( 'Zero PII • Strict Medical Advice Refusal Barrier Active', 'pi-insights-dashboard' ); ?></span>
		</div>

		<div class="pi-prompt-pills">
			<span class="pi-pill-label"><?php esc_html_e( 'Suggested Queries:', 'pi-insights-dashboard' ); ?></span>
			<button type="button" class="pi-pill" data-q="Which channel has the highest conversion rate?"><?php esc_html_e( 'Email vs Channel Conversion', 'pi-insights-dashboard' ); ?></button>
			<button type="button" class="pi-pill" data-q="Why does mobile have a higher bounce rate?"><?php esc_html_e( 'Mobile Bounce Gap', 'pi-insights-dashboard' ); ?></button>
			<button type="button" class="pi-pill" data-q="How is PRR calculated in pharmacovigilance?"><?php esc_html_e( 'PRR & ROR Formulas', 'pi-insights-dashboard' ); ?></button>
			<button type="button" class="pi-pill" data-q="What are the key limitations of FAERS data?"><?php esc_html_e( 'FAERS Limitations', 'pi-insights-dashboard' ); ?></button>
			<button type="button" class="pi-pill" data-q="What caused the traffic anomaly in March 2026?"><?php esc_html_e( 'March Anomaly Analysis', 'pi-insights-dashboard' ); ?></button>
		</div>

		<form id="pi-assistant-form" class="pi-assistant-form">
			<div class="pi-input-group">
				<label for="pi-assistant-input" class="screen-reader-text"><?php esc_html_e( 'Ask a question about the platform', 'pi-insights-dashboard' ); ?></label>
				<input type="text" id="pi-assistant-input" placeholder="<?php esc_attr_e( 'Ask about omnichannel KPIs, pharmacovigilance methodology, or dataset anomalies...', 'pi-insights-dashboard' ); ?>" required />
				<button type="submit" id="pi-assistant-btn" class="pi-cta-button"><?php esc_html_e( 'Ask Assistant &rarr;', 'pi-insights-dashboard' ); ?></button>
			</div>
		</form>

		<div id="pi-assistant-loading" style="display:none;" class="pi-assistant-loading">
			<em><?php esc_html_e( 'Analyzing platform knowledge base and computed KPIs...', 'pi-insights-dashboard' ); ?></em>
		</div>

		<div id="pi-assistant-response" style="display:none;" class="pi-assistant-response">
			<div class="pi-response-header">
				<strong><?php esc_html_e( 'Assistant Response:', 'pi-insights-dashboard' ); ?></strong>
				<span id="pi-response-mode" class="pi-tag tag-positive"></span>
			</div>
			<div id="pi-response-text" class="pi-response-text"></div>
			<div id="pi-response-sources" class="pi-response-sources"></div>
		</div>
	</div>

	<script>
	document.addEventListener("DOMContentLoaded", function() {
		var form = document.getElementById("pi-assistant-form");
		var input = document.getElementById("pi-assistant-input");
		var loading = document.getElementById("pi-assistant-loading");
		var responseDiv = document.getElementById("pi-assistant-response");
		var responseText = document.getElementById("pi-response-text");
		var responseMode = document.getElementById("pi-response-mode");
		var responseSources = document.getElementById("pi-response-sources");

		document.querySelectorAll(".pi-pill").forEach(function(btn) {
			btn.addEventListener("click", function() {
				input.value = this.getAttribute("data-q");
				form.dispatchEvent(new Event("submit"));
			});
		});

		form.addEventListener("submit", function(e) {
			e.preventDefault();
			var q = input.value.trim();
			if (!q) return;

			loading.style.display = "block";
			responseDiv.style.display = "none";

			fetch("/wp-json/pi-insights/v1/chat", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({ question: q })
			})
			.then(function(res) { return res.json(); })
			.then(function(data) {
				loading.style.display = "none";
				responseDiv.style.display = "block";

				if (data.error) {
					responseText.innerHTML = '<p class="text-negative">' + data.error + '</p>';
					responseMode.textContent = "Error";
					responseSources.innerHTML = "";
					return;
				}

				responseMode.textContent = data.mode === "live_llm_proxy" ? "Live LLM" : (data.mode === "refusal_guardrail" ? "Refusal Guardrail" : "Offline Knowledge");
				responseText.innerHTML = '<p>' + (data.answer || '').replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>') + '</p>';

				if (data.sources && data.sources.length) {
					var sHtml = '<strong>Citations & Grounding:</strong><ul>';
					data.sources.forEach(function(s) { sHtml += '<li>' + s + '</li>'; });
					sHtml += '</ul>';
					responseSources.innerHTML = sHtml;
				} else {
					responseSources.innerHTML = "";
				}
			})
			.catch(function(err) {
				loading.style.display = "none";
				responseDiv.style.display = "block";
				responseText.innerHTML = '<p>An unexpected network error occurred.</p>';
			});
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'pi_assistant', 'pi_assistant_shortcode' );

