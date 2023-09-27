<?php
/**
 * Admin class.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Admin
 * @author  David Paternina
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register menu items for UserFeedback.
 *
 * @since 1.0.0
 * @access public
 *
 * @return void
 */
function userfeedback_admin_menu() {

	$menu_slug = 'userfeedback_surveys';

	// Add main Menu Item
	add_menu_page(
		__( 'UserFeedback', 'userfeedback' ),
		__( 'UserFeedback', 'userfeedback' ) . UserFeedback()->notifications->get_count_for_admin_sidebar(),
		'userfeedback_create_edit_surveys',
		$menu_slug,
		'userfeedback_surveys_page',
		USERFEEDBACK_PLUGIN_URL . 'assets/img/logo-outline.svg',
		'100'
	);

	// Surveys
	add_submenu_page(
		$menu_slug,
		__( 'Surveys', 'userfeedback' ),
		__( 'Surveys', 'userfeedback' ),
		'userfeedback_create_edit_surveys',
		'userfeedback_surveys',
		'userfeedback_surveys_page'
	);

	// Results
	add_submenu_page(
		$menu_slug,
		__( 'Results', 'userfeedback' ),
		__( 'Results', 'userfeedback' ),
		'userfeedback_view_results',
		'userfeedback_results',
		'userfeedback_results_page'
	);

	$settings_menu_slug = 'userfeedback_settings';

	// Settings
	add_submenu_page(
		$menu_slug,
		__( 'Settings', 'userfeedback' ),
		__( 'Settings', 'userfeedback' ),
		'userfeedback_save_settings',
		$settings_menu_slug,
		'userfeedback_settings_page'
	);

	$settings_submenu_base = add_query_arg( 'page', $settings_menu_slug, admin_url( 'admin.php' ) );

	// Addons
	add_submenu_page(
		$menu_slug,
		__( 'Addons', 'userfeedback' ),
		'<b style="color: ' . userfeedback_menu_highlight_color() . '">' . __( 'Addons', 'userfeedback' ) . '</b>',
		'manage_options',
		$settings_submenu_base . '#/addons'
	);

	//  Integrations
	add_submenu_page(
		$menu_slug,
		__( 'Integrations', 'userfeedback' ),
		'<span>' . __( 'Integrations', 'userfeedback' ) . '</span>',
		'manage_options',
		$settings_submenu_base . '#/integrations'
	);

	// SMTP
	add_submenu_page(
		$menu_slug,
		__( 'SMTP', 'userfeedback' ),
		__( 'SMTP', 'userfeedback' ),
		'manage_options',
		'userfeedback_smtp',
		'userfeedback_smtp_page'
	);
	
	// About Us
	add_submenu_page(
		$menu_slug,
		__( 'About Us', 'userfeedback' ),
		__( 'About Us', 'userfeedback' ),
		'manage_options',
		$settings_submenu_base . '#/about'
	);

	if ( ! userfeedback_is_pro_version() ) {
		add_submenu_page(
			$menu_slug,
			__( 'Upgrade to Pro:', 'google-analytics-for-wordpress' ),
			'<span class="userfeedback-upgrade-submenu"> ' . __( 'Upgrade to Pro', 'userfeedback' ) . '</span>',
			'userfeedback_save_settings',
			userfeedback_get_upgrade_link( 'admin-menu', 'submenu', 'https://www.userfeedback.com/lite/' )
		);
	}

}
add_action( 'admin_menu', 'userfeedback_admin_menu' );

// ----------------------------------------------------
// ------------ Menu Callback functions ---------------
// ----------------------------------------------------

/**
 * Render UserFeedback Surveys page
 *
 * @return void
 */
function userfeedback_surveys_page() {
	echo '<div id="userfeedback-surveys"></div>';
}

/**
 * Render UserFeedback Results page
 *
 * @return void
 */
function userfeedback_results_page() {
	echo '<div id="userfeedback-results"></div>';
}

/**
 * Render UserFeedback Settings page
 *
 * @return void
 */
function userfeedback_settings_page() {
	echo '<div id="userfeedback-settings"></div>';
}

/**
 * Render UserFeedback SMTP page
 *
 * @return void
 */
function userfeedback_smtp_page() {
	echo '<div id="userfeedback-smtp"></div>';
}

// ----------------------------------------------------
// ---------------- Additional hooks ------------------
// ----------------------------------------------------

function userfeedback_hide_admin_notices() {
	if ( userfeedback_screen_is_userfeedback() ) {
		remove_all_actions( 'admin_notices' );
	}
}
add_action( 'admin_head', 'userfeedback_hide_admin_notices', 1 );

/**
 * Add a link to the settings page to the plugins list
 *
 * @param array $links array of links for the plugins, adapted when the current plugin is found.
 *
 * @return array $links
 */
function userfeedback_add_action_links( $links ) {

	$docs = '<a title="' . esc_attr__( 'UserFeedback Knowledge Base', 'google-analytics-for-wordpress' ) . '" target="_blank" rel="noopener" href="' . userfeedback_get_url( 'all-plugins', 'kb-link', 'https://www.userfeedback.com/docs/' ) . '">' . esc_html__( 'Documentation', 'userfeedback' ) . '</a>';
	array_unshift( $links, $docs );

	// If Lite, support goes to forum. If pro, it goes to our website
	if ( userfeedback_is_pro_version() ) {
		$support = '<a title="UserFeedback Pro Support" target="_blank" rel="noopener" href="' . userfeedback_get_url( 'all-plugins', 'pro-support-link', 'https://www.userfeedback.com/my-account/support/' ) . '">' . esc_html__( 'Support', 'userfeedback' ) . '</a>';
		array_unshift( $links, $support );
	} else {
		$support = '<a title="UserFeedback Lite Support" target="_blank" rel="noopener" href="' . userfeedback_get_url( 'all-plugins', 'lite-support-link', 'https://www.userfeedback.com/lite-support/' ) . '">' . esc_html__( 'Support', 'userfeedback' ) . '</a>';
		array_unshift( $links, $support );
	}

	$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=userfeedback_settings' ) ) . '">' . esc_html__( 'Settings', 'userfeedback' ) . '</a>';
	array_unshift( $links, $settings_link );

	// If lite, show a link where they can get pro from
	if ( ! userfeedback_is_pro_version() ) {
		$get_pro = '<a title="' . esc_attr__( 'Get UserFeedback Pro', 'userfeedback' ) . '" target="_blank" rel="noopener" href="' . userfeedback_get_upgrade_link( 'all-plugins', 'upgrade-link', 'https://www.userfeedback.com/lite/' ) . '" style="font-weight:700; color: #1da867;">' . esc_html__( 'Get UserFeedback Pro', 'userfeedback' ) . '</a>';
		array_unshift( $links, $get_pro );
	}

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( USERFEEDBACK_PLUGIN_FILE ), 'userfeedback_add_action_links' );

/**
 * Adds one or more classes to the body tag in the dashboard.
 *
 * @param  String $classes Current body classes.
 * @return String          Altered body classes.
 */
function userfeedback_add_admin_body_class( $classes ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
	if ( empty( $screen ) || empty( $screen->id ) || strpos( $screen->id, 'userfeedback' ) === false ) {
		return $classes;
	}

	return "$classes userfeedback_page ";
}
add_filter( 'admin_body_class', 'userfeedback_add_admin_body_class', 10, 1 );


// ----------------------------------------------------
// ---------------- Onboarding Launch ------------------
// ----------------------------------------------------

function userfeedback_onboarding_first_launch()
{
	$surveys = UserFeedback_Survey::all();
	if (userfeedback_screen_is_userfeedback() && isset($_GET['page']) && 'userfeedback_surveys' === $_GET['page'] && empty($surveys)) {
		$surveys_screen_first_visit = userfeedback_get_option('userfeedback_surveys_screen_first_visit', false);
		$userfeedback_onboarding_step = userfeedback_get_option('userfeedback_onboarding_step', false);
		if (!$surveys_screen_first_visit && !$userfeedback_onboarding_step) {
			userfeedback_update_option('userfeedback_surveys_screen_first_visit', true);
			wp_redirect(admin_url('admin.php?page=userfeedback_onboarding'));
			die();
		}
	}
}
add_action( 'current_screen', 'userfeedback_onboarding_first_launch', 1 );


