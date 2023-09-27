<?php

/**
 * Common admin class.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Common
 * @author  David Paternina
 */

// Exit if accessed directly
if ( !defined('ABSPATH') ) {
	exit;
}

/**
 * Called whenever an upgrade button / link is displayed in Lite, this function will
 * check if there's a shareasale ID specified.
 *
 * There are three ways to specify an ID, ordered by highest to lowest priority
 * - add_filter( 'userfeedback_shareasale_id', function() { return 1234; } );
 * - define( 'USERFEEDBACK_SHAREASALE_ID', 1234 );
 * - get_option( 'userfeedback_shareasale_id' ); (with the option being in the wp_options table)
 *
 * If an ID is present, returns the ShareASale link with the affiliate ID, and tells
 * ShareASale to then redirect to userfeedback.com/lite
 *
 * If no ID is present, just returns the userfeedback.com/lite URL with UTM tracking.
 *
 * @return string Upgrade link.
 * @since 6.0.0
 * @access public
 */
function userfeedback_get_upgrade_link( $medium = '', $campaign = '', $url = '' ) {
	$url = userfeedback_get_url( $medium, $campaign, $url, false );

	if ( userfeedback_is_pro_version() ) {
		return esc_url( $url );
	}

	// Get the ShareASale ID
	// $shareasale_id = userfeedback_get_shareasale_id();

	// If we have a shareasale ID return the shareasale url
	// if ( ! empty( $shareasale_id ) ) {
	// $shareasale_id = absint( $shareasale_id );
	//
	// return esc_url( monsterinsights_get_shareasale_url( $shareasale_id, $url ) );
	// }

	return esc_url( $url );
}

function userfeedback_get_url( $medium = '', $campaign = '', $url = '', $escape = true ) {

	$is_pro = userfeedback_is_pro_version();

	// Setup Campaign variables
	$source      = $is_pro ? 'proplugin' : 'liteplugin';
	$medium      = ! empty( $medium ) ? $medium : 'defaultmedium';
	$campaign    = ! empty( $campaign ) ? $campaign : 'defaultcampaign';
	$content     = USERFEEDBACK_VERSION;
	$default_url = $is_pro ? '' : 'lite/';
	$url         = ! empty( $url ) ? $url : 'https://www.userfeedback.com/' . $default_url;

	// Put together redirect URL
	$url = add_query_arg(
		array(
			'utm_source'   => $source,   // Pro/Lite Plugin
			'utm_medium'   => sanitize_key( $medium ),   // Area of UserFeedback (example Surveys)
			'utm_campaign' => sanitize_key( $campaign ), // Which link
			'utm_content'  => $content,  // Version number of UF
		),
		trailingslashit( $url )
	);

	if ( $escape ) {
		return esc_url( $url );
	} else {
		return $url;
	}
}

/**
 * Get admin asset full URL
 *
 * @since 1.0.0
 * @param $path
 * @return mixed|void
 */
function userfeedback_get_admin_asset_url( $path ) {
	return esc_url(
		apply_filters(
			'userfeedback_admin_assets_url',
			plugins_url( $path, USERFEEDBACK_PLUGIN_FILE ),
			$path
		)
	);
}

/**
 * Loads styles for all UserFeedback-based Administration Screens.
 *
 * @since 1.0.0
 * @access public
 */
function userfeedback_admin_styles() {

	wp_enqueue_style(
		'userfeedback-admin',
		plugins_url( '/assets/css/uf-admin.css', USERFEEDBACK_PLUGIN_FILE ),
		array(),
		userfeedback_get_asset_version()
	);

	// Bail if we're not on a UserFeedback screen or WP dashboard
	if ( ! userfeedback_screen_is_userfeedback() && ! userfeedback_screen_is_wp_dashboard() ) {
		return;
	}

	if ( userfeedback_screen_is_surveys() ) {
		wp_enqueue_style(
			'userfeedback-vue-surveys',
			userfeedback_get_admin_asset_url( '/assets/vue/css/surveys.css' ),
			array(),
			userfeedback_get_asset_version()
		);

		wp_enqueue_style(
			'userfeedback-frontend-styles',
			UserFeedback_Frontend::get_frontend_asset_url( '/assets/vue/css/frontend.css' ),
			array(),
			userfeedback_get_asset_version()
		);
	}

	if ( userfeedback_screen_is_results() ) {
		wp_enqueue_style(
			'userfeedback-vue-results',
			userfeedback_get_admin_asset_url( '/assets/vue/css/results.css' ),
			array(),
			userfeedback_get_asset_version()
		);
	}

	if ( userfeedback_screen_is_settings() ) {
		wp_enqueue_style(
			'userfeedback-vue-settings',
			userfeedback_get_admin_asset_url( '/assets/vue/css/settings.css' ),
			array(),
			userfeedback_get_asset_version()
		);
		// Load frontend widget styles
		wp_enqueue_style(
			'userfeedback-frontend-styles',
			UserFeedback_Frontend::get_frontend_asset_url( '/assets/vue/css/frontend.css' ),
			array(),
			userfeedback_get_asset_version()
		);
	}
	
	if ( userfeedback_screen_is_smtp() ) {
		wp_enqueue_style(
			'userfeedback-vue-smtp',
			userfeedback_get_admin_asset_url( '/assets/vue/css/smtp.css' ),
			array(),
			userfeedback_get_asset_version()
		);
	}

	if ( userfeedback_screen_is_wp_dashboard() ) {
		wp_enqueue_style(
			'userfeedback-vue-dashboard-widget',
			userfeedback_get_admin_asset_url( '/assets/vue/css/dashboard-widget.css' ),
			array(),
			userfeedback_get_asset_version()
		);
	}
}
add_action( 'admin_enqueue_scripts', 'userfeedback_admin_styles' );

/**
 * Loads scripts for all UserFeedback-based Administration Screens.
 *
 * @since 1.0.0
 * @access public
 */
function userfeedback_admin_scripts() {

	// Small, generic scripts
	wp_register_script(
		'userfeedback-admin',
		plugins_url( '/assets/js/uf-admin.js', USERFEEDBACK_PLUGIN_FILE ),
		array(),
		userfeedback_get_asset_version(),
		true
	);
	wp_enqueue_script( 'userfeedback-admin' );

	// Bail if we're not on a UserFeedback screen or WP dashboard
	if ( ! userfeedback_screen_is_userfeedback() && ! userfeedback_screen_is_wp_dashboard() ) {
		return;
	}

	// --------------------------------------------------
	// ---------------- Common scripts ------------------
	wp_register_script(
		'userfeedback-vue-chunk-vendors',
		userfeedback_get_admin_asset_url( '/assets/vue/js/chunk-vendors.js' ),
		array(),
		userfeedback_get_asset_version(),
		true
	);
	wp_enqueue_script( 'userfeedback-vue-chunk-vendors' );

	wp_register_script(
		'userfeedback-vue-chunk-common',
		userfeedback_get_admin_asset_url( '/assets/vue/js/chunk-common.js' ),
		array(),
		userfeedback_get_asset_version(),
		true
	);
	wp_enqueue_script( 'userfeedback-vue-chunk-common' );

	// Let's add an empty array for addons on all UF pages
	wp_localize_script(
		'userfeedback-vue-chunk-common',
		'userfeedback_addons',
		array()
	);
	// --------------------------------------------------

	// --------------------------------------------------
	// --------------- Surveys scripts ------------------
	if ( userfeedback_screen_is_surveys() ) {

		wp_register_script(
			'userfeedback-vue-surveys-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/surveys.js' ),
			apply_filters( 'userfeedback_surveys_script_dependencies', array() ),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-surveys-script' );
		wp_localize_script(
			'userfeedback-vue-surveys-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);
	}
	// --------------------------------------------------

	// --------------------------------------------------
	// --------------- Results scripts ------------------
	if ( userfeedback_screen_is_results() ) {

		wp_register_script(
			'userfeedback-vue-results-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/results.js' ),
			apply_filters( 'userfeedback_results_script_dependencies', array() ),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-results-script' );
		wp_localize_script(
			'userfeedback-vue-results-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);
	}
	// --------------------------------------------------

	// --------------------------------------------------
	// -------------- Settings scripts ------------------
	if ( userfeedback_screen_is_settings() ) {

		wp_register_script(
			'userfeedback-vue-settings-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/settings.js' ),
			apply_filters( 'userfeedback_settings_script_dependencies', array() ),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-settings-script' );
		wp_localize_script(
			'userfeedback-vue-settings-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);
	}
	
	// --------------------------------------------------
	// -------------- SMTP scripts ------------------
	if ( userfeedback_screen_is_smtp() ) {

		wp_register_script(
			'userfeedback-vue-smtp-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/smtp.js' ),
			apply_filters( 'userfeedback_smtp_script_dependencies', array() ),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-smtp-script' );
		wp_localize_script(
			'userfeedback-vue-smtp-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);
	}

	// ---------------------------------------------------------
	// -------------- Dashboard Widget scripts ------------------
	if ( userfeedback_screen_is_wp_dashboard() ) {

		wp_register_script(
			'userfeedback-vue-dashboard-widget-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/dashboard-widget.js' ),
			array(),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-dashboard-widget-script' );
		wp_localize_script(
			'userfeedback-vue-dashboard-widget-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);
	}

}

add_action( 'admin_enqueue_scripts', 'userfeedback_admin_scripts', 99 );

// ----------------------------------------------------
// --------------------- Helpers ----------------------
// ----------------------------------------------------

function userfeedback_get_common_script_localization_object() {
	return apply_filters(
		'userfeedback_admin_script_localization',
		array(
			'ajax'                      => admin_url( 'admin-ajax.php' ),
			'nonce'                     => wp_create_nonce( 'uf-admin-nonce' ),
			'wp_rest_nonce'             => wp_create_nonce( 'wp_rest' ),
			'activate_nonce'            => wp_create_nonce( 'userfeedback-activate' ),
			'deactivate_nonce'          => wp_create_nonce( 'userfeedback-deactivate' ),
			'install_nonce'             => wp_create_nonce( 'userfeedback-install' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'rest_url'                  => rest_url(),
			'admin_url'                 => admin_url(),
			'settings_url'                 => admin_url('admin.php?page=userfeedback_settings'),
			'email_summary_preview_url' => admin_url( 'admin.php?userfeedback_email_preview&userfeedback_email_template=summary' ),
			'admin_email'               => get_option( 'admin_email' ),
			'is_pro'                    => userfeedback_is_pro_version(),
			'is_licensed'               => userfeedback_is_licensed(),
			'license_type'              => userfeedback_get_license_type(),
			'roles'                     => userfeedback_get_roles(),
			'roles_manage_options'      => userfeedback_get_manage_options_roles(),
			'plugin_version'            => USERFEEDBACK_VERSION,
			'translations'              => wp_get_jed_locale_data( 'userfeedback' ),
			'assets'                    => plugins_url( '/assets/vue', USERFEEDBACK_PLUGIN_FILE ),
			'integrations'              => array(),
			'addons'                    => userfeedback_get_parsed_addons(),
			'notices'                   => apply_filters( 'userfeedback_vue_notices', array() ),
			'wp_notices'                => apply_filters( 'userfeedback_vue_wp_notices', array() ),
			'widget_settings'           => userfeedback_get_frontend_widget_settings()
		)
	);
}

/**
 * Get and save parsed addons if not present to use the data in localizations scripts.
 */
function userfeedback_save_parsed_addons() {
	$saved_parsed_addons = get_option('userfeedback_parsed_addons', false);
	if(!$saved_parsed_addons) {
		$addons = userfeedback_get_parsed_addons();
		update_option( 'userfeedback_parsed_addons', $addons );
		$saved_parsed_addons = get_option('userfeedback_parsed_addons');
	}
	return $saved_parsed_addons;
}
/* This will ensure saved data in userfeedback_parsed_addons option on upgrade. */
add_action('admin_init', 'userfeedback_save_parsed_addons');