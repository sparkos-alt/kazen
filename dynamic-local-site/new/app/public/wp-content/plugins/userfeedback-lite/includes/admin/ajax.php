<?php

/**
 * Handles all admin ajax interactions for the UserFeedback plugin.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Ajax
 * @author  David Paternina
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region Notices

/**
 * Called whenever a notice is dismissed in UserFeedback or its Addons.
 *
 * Updates a key's value in the options table to mark the notice as dismissed,
 * preventing it from displaying again
 *
 * @access public
 * @since 1.0.0
 */
function userfeedback_ajax_dismiss_notice() {
	// Run a security check first.
	check_ajax_referer( 'userfeedback-dismiss-notice', 'nonce' );
	$post_data = sanitize_post( $_POST, 'raw' );
	// Deactivate the notice
	if ( isset( $post_data['notice'] ) ) {
		// Init the notice class and mark notice as deactivated
		UserFeedback()->notices->dismiss( $post_data['notice'] );

		// Return true
		echo json_encode( true );
		wp_die();
	}

	// If here, an error occurred
	echo json_encode( false );
	wp_die();
}
add_action( 'wp_ajax_userfeedback_ajax_dismiss_notice', 'userfeedback_ajax_dismiss_notice' );

function userfeedback_ajax_vue_remove_notice() {
	// Run a security check first.
	check_ajax_referer( 'uf-admin-nonce', 'nonce' );

	// Deactivate the notice
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( isset( $post_data['notice_id'] ) ) {
		$key = userfeedback_get_notice_hide_opt_prefix() . $post_data['notice_id'];
		update_option( $key, time() );
		update_user_meta( get_current_user_id(), $key, time() );

		// Return true
		echo json_encode( true );
		wp_die();
	}

	// If here, an error occurred
	echo json_encode( false );
	wp_die();
}
add_action( 'wp_ajax_userfeedback_ajax_vue_remove_notice', 'userfeedback_ajax_vue_remove_notice' );


function userfeedback_ajax_vue_remove_wp_notice() {
	// Run a security check first.
	check_ajax_referer( 'uf-admin-nonce', 'nonce' );

	// Deactivate the notice
	$post_data = sanitize_post( $_POST, 'raw' );
	
	if ( isset( $post_data['notice_id'] ) ) {
		$key = userfeedback_get_wp_notice_hide_opt_prefix() . $post_data['notice_id'];
		update_option( $key, time() );
		update_user_meta( get_current_user_id(), $key, time() );

		// Return true
		echo json_encode( true );
		wp_die();
	}

	// If here, an error occurred
	echo json_encode( false );
	wp_die();
}
add_action( 'wp_ajax_userfeedback_ajax_vue_remove_wp_notice', 'userfeedback_ajax_vue_remove_wp_notice' );

// endregion

/**
 * Mark onboarding as complete
 *
 * @return void
 */
function userfeedback_complete_onboarding() {
	check_ajax_referer( 'uf-admin-nonce', 'nonce' );
	// Set this option to prevent WP Forms setup from showing up after the wizard completes.
	update_option( 'wpforms_activation_redirect', true );
	delete_transient( 'wp_mail_smtp_activation_redirect' );
	// Set this option to prevent All In One SEO setup from showing up after the wizard completes.
	update_option( 'aioseo_activation_redirect', true );

	update_option( 'userfeedback_onboarding_complete', true );
	wp_die();
}
add_action( 'wp_ajax_userfeedback_vue_onboarding_complete', 'userfeedback_complete_onboarding' );


function userfeedback_onboarding_drop_opt_in() {
	$post_data = sanitize_post( $_POST, 'raw' );
	// Drip register
	$email = sanitize_email( $post_data['email'] );

	if ( empty( $email ) ) {
		return;
	}

	$plugin = userfeedback_is_pro_version() ? 'pro' : 'lite';

	$body = http_build_query(
		array(
			'userfeedback-drip-register' => '',
			'userfeedback-drip-email'    => base64_encode( $email ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		'userfeedback-drip-plugin'       => $plugin,
		),
		'',
		'&'
	);

	wp_remote_post(
		userfeedback_get_licensing_url(),
		array(
			'headers' => array(
				'Content-Type'   => 'application/x-www-form-urlencoded',
				'Content-Length' => strlen( $body ),
			),
			'body'    => $body,
		)
	);

	wp_die();
}

add_action( 'wp_ajax_userfeedback_vue_onboarding_drip_opt_in', 'userfeedback_onboarding_drop_opt_in' );


/**
 * Save onboarding current step
 *
 * @return void
 */
function userfeedback_vue_onboarding_step() {
	if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
		return;
	}
	// update onboarding step
	userfeedback_update_option('userfeedback_onboarding_step', $_POST['step']);
	wp_die();
}
add_action( 'wp_ajax_userfeedback_vue_onboarding_step', 'userfeedback_vue_onboarding_step' );


/**
 * Validate settings blurb
 *
 * @return void
 */
function userfeedback_validate_settings_blurb() {
	$return = get_user_meta( get_current_user_id(), 'userfeedback-dismiss-settings-blurb', true );
	echo json_encode($return);
	wp_die();
}
add_action( 'wp_ajax_userfeedback_validate_settings_blurb', 'userfeedback_validate_settings_blurb' );

/**
 * Dismiss settings blurb
 *
 * @return void
 */
function userfeedback_dismiss_settings_blurb() {
	update_user_meta( get_current_user_id(), 'userfeedback-dismiss-settings-blurb', true );
	echo json_encode( true );
	wp_die();
}
add_action( 'wp_ajax_userfeedback_dismiss_settings_blurb', 'userfeedback_dismiss_settings_blurb' );

