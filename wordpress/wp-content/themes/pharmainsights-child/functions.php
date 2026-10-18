<?php
/**
 * PharmaInsights Child Theme Functions
 *
 * @package PharmaInsights_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function pharmainsights_setup_theme() {
	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'pharmainsights-child' ),
		'footer'  => __( 'Footer Navigation', 'pharmainsights-child' ),
	) );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'pharmainsights_setup_theme' );

/**
 * Enqueue parent and child stylesheets
 */
function pharmainsights_enqueue_styles() {
	wp_enqueue_style(
		'twentytwentyfive-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme()->parent() ? wp_get_theme()->parent()->get( 'Version' ) : '1.0'
	);

	wp_enqueue_style(
		'pharmainsights-child-style',
		get_stylesheet_uri(),
		array( 'twentytwentyfive-parent-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'pharmainsights_enqueue_styles' );

/**
 * Register block pattern category
 */
function pharmainsights_register_pattern_categories() {
	register_block_pattern_category(
		'pharmainsights',
		array(
			'label' => __( 'PharmaInsights Hub', 'pharmainsights-child' ),
		)
	);
}
add_action( 'init', 'pharmainsights_register_pattern_categories' );

/**
 * Register reusable block patterns
 */
function pharmainsights_register_block_patterns() {
	// Disclaimer Banner Pattern
	register_block_pattern(
		'pharmainsights/disclaimer-notice',
		array(
			'title'       => __( 'Educational Medical Disclaimer', 'pharmainsights-child' ),
			'categories'  => array( 'pharmainsights' ),
			'description' => __( 'Mandatory non-promotional educational medical disclaimer', 'pharmainsights-child' ),
			'content'     => '<!-- wp:group {"className":"pi-disclaimer-box","layout":{"type":"constrained"}} -->
<div class="wp-block-group pi-disclaimer-box">
<p><strong>Educational Disclaimer: Not Medical Advice</strong>This publication is designed exclusively for educational, data analysis, and informational research purposes. The content does not constitute medical, clinical, pharmacological, or regulatory advice. For clinical inquiries or adverse event reporting, always consult licensed healthcare professionals and official public health authority portals.</p>
</div>
<!-- /wp:group -->',
		)
	);

	// Actionable CTA Card Pattern
	register_block_pattern(
		'pharmainsights/cta-omnichannel',
		array(
			'title'       => __( 'Omnichannel Analytics CTA', 'pharmainsights-child' ),
			'categories'  => array( 'pharmainsights' ),
			'description' => __( 'Call to action card for exploring interactive digital dashboards', 'pharmainsights-child' ),
			'content'     => '<!-- wp:group {"className":"pi-cta-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group pi-cta-card">
<h3>Explore Interactive Omnichannel Intelligence</h3>
<p>Delve into synthetic performance benchmarks, channel mix conversions, and anomaly detection models built for life-sciences analytics teams.</p>
<p><a class="pi-cta-button" href="/digital-performance-dashboard/">Open Performance Dashboard &rarr;</a></p>
</div>
<!-- /wp:group -->',
		)
	);
}
add_action( 'init', 'pharmainsights_register_block_patterns' );

/**
 * Register "Insight" Custom Post Type for structured data intelligence
 */
function pharmainsights_register_post_types() {
	$labels = array(
		'name'               => _x( 'Insights', 'post type general name', 'pharmainsights-child' ),
		'singular_name'      => _x( 'Insight', 'post type singular name', 'pharmainsights-child' ),
		'menu_name'          => _x( 'Insights', 'admin menu', 'pharmainsights-child' ),
		'name_admin_bar'     => _x( 'Insight', 'add new on admin bar', 'pharmainsights-child' ),
		'add_new'            => _x( 'Add New Insight', 'insight', 'pharmainsights-child' ),
		'add_new_item'       => __( 'Add New Insight', 'pharmainsights-child' ),
		'new_item'           => __( 'New Insight', 'pharmainsights-child' ),
		'edit_item'          => __( 'Edit Insight', 'pharmainsights-child' ),
		'view_item'          => __( 'View Insight', 'pharmainsights-child' ),
		'all_items'          => __( 'All Insights', 'pharmainsights-child' ),
		'search_items'       => __( 'Search Insights', 'pharmainsights-child' ),
		'not_found'          => __( 'No insights found.', 'pharmainsights-child' ),
		'not_found_in_trash' => __( 'No insights found in Trash.', 'pharmainsights-child' ),
	);

	$args = array(
		'labels'             => $labels,
		'description'        => __( 'Specialized data briefings and pharmacovigilance insights.', 'pharmainsights-child' ),
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'insights-archive' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 5,
		'menu_icon'          => 'dashicons-chart-bar',
		'show_in_rest'       => true,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
		'taxonomies'         => array( 'category', 'post_tag' ),
	);

	register_post_type( 'insight', $args );

	// Register Custom Field "Data Source" for Insight CPT
	register_post_meta(
		'insight',
		'_pi_data_source',
		array(
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'pharmainsights_register_post_types' );

/**
 * Add accessible Skip Link to top of body if not rendered by template
 */
function pharmainsights_add_skip_link() {
	echo '<a class="skip-link screen-reader-text" href="#content">' . esc_html__( 'Skip to content', 'pharmainsights-child' ) . '</a>';
}
add_action( 'wp_body_open', 'pharmainsights_add_skip_link', 1 );

/**
 * SEO & Open Graph Meta Tags Fallback (Enhancement alongside Yoast SEO)
 */
function pharmainsights_seo_meta_tags() {
	if ( is_front_page() ) {
		echo '<meta name="description" content="PharmaInsights Hub: Educational research, pharmacovigilance data methodology, and omnichannel healthcare digital performance intelligence." />' . "\n";
		echo '<meta property="og:title" content="PharmaInsights Hub - Healthcare Analytics & Digital Performance" />' . "\n";
		echo '<meta property="og:description" content="Educational platform for life-sciences omnichannel analytics, signal detection, and reporting automation." />' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '" />' . "\n";
	}
}
add_action( 'wp_head', 'pharmainsights_seo_meta_tags', 1 );
