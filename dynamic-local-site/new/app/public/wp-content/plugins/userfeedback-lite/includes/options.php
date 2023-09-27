<?php
/**
 * Option functions.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Options
 * @author  David Paternina
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function userfeedback_get_option_name() {
	return 'userfeedback_settings';
}

/**
 * Get UserFeedback default config values
 *
 * @return array
 */
function userfeedback_get_default_options() {
	$admin_email       = get_option( 'admin_email' );
	$admin_email_array = array( $admin_email );

	return array(
		// Widget
		'widget_theme'                => 'light',
		'widget_color'                => '#ffffff',
		'widget_text_color'           => '#23282D',
		'widget_button_color'         => '#2D87F1',
		'widget_start_minimized'      => false,
		'widget_show_logo'            => false,
		'widget_custom_logo'          => '',
		'widget_position'             => 'bottom_right',
		// Notifications
		'notifications_recipients'    => '',
		'notifications_html_template' => true,
		'notifications_header_image'  => '',
		// UF settings permissions
		'save_settings'               => array( 'administrator' ),
		// UF Surveys / Responses permissions
		'create_edit_surveys'         => array( 'administrator', 'editor' ),
		'view_results'                => array( 'administrator', 'editor' ),
		'delete_surveys'              => array( 'administrator', 'editor' ),
		'allow_usage_tracking'        => true,
		// Email summaries
		'summaries_disabled'          => false,
		'summaries_html_template'     => true,
		'summaries_email_addresses'   => $admin_email_array,
	);
}

/**
 * Get UserFeedback config values
 *
 * @return array|mixed|void
 */
function userfeedback_get_options() {
	$settings = get_option( userfeedback_get_option_name() );

	if ( empty( $settings ) || ! is_array( $settings ) ) {
		$settings = array();
	}

	return $settings;
}

/**
 * Update UserFeedback config values.
 *
 * Merges the $settings parameter with the result of userfeedback_get_default_options
 * to guarantee that all options are always available.
 *
 * @see userfeedback_get_default_options()
 * @param $settings
 * @return boolean
 */
function userfeedback_save_options( $settings ) {
	if ( empty( $settings ) || ! is_array( $settings ) ) {
		return false;
	}

	$old_settings = userfeedback_get_options();
	$settings     = array_merge( userfeedback_get_default_options(), $old_settings, $settings );
	return update_option( userfeedback_get_option_name(), $settings );
}

/**
 * Helper method for getting a setting's value. Falls back to the default
 * setting value if none exists in the options table.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $key   The setting key to retrieve.
 * @param mixed  $default   The default value of the setting key to retrieve.
 * @return mixed       The value of the setting.
 */
function userfeedback_get_option( $key = '', $default = false ) {
	global $userfeedback_settings;

	$value = ! empty( $userfeedback_settings[ $key ] ) ? $userfeedback_settings[ $key ] : $default;
	$value = apply_filters( 'userfeedback_get_option', $value, $key, $default );

	return apply_filters( 'userfeedback_get_option_' . $key, $value, $key, $default );
}

/**
 * Helper method for updating a setting's value.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $key   The setting key.
 * @param string $value The value to set for the key.
 * @return boolean True if updated, false if not.
 */
function userfeedback_update_option( $key = '', $value = false ) {

	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		return userfeedback_delete_option( $key );
	}

	$option_name = userfeedback_get_option_name();

	$settings = userfeedback_get_options();

	// Let's let devs alter that value coming in
	$value = apply_filters( 'userfeedback_update_option', $value, $key );

	// Next let's try to update the value
	$settings[ $key ] = $value;

	$did_update = update_option( $option_name, $settings );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $userfeedback_settings;
		$userfeedback_settings[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Helper method for deleting a setting's value.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $key   The setting key.
 * @return boolean True if removed, false if not.
 */
function userfeedback_delete_option( $key = '' ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	$option_name = userfeedback_get_option_name();

	// First let's grab the current settings
	$settings = userfeedback_get_options();

	// Next let's try to remove the key
	if ( isset( $settings[ $key ] ) ) {
		unset( $settings[ $key ] );
	}

	$did_update = update_option( $option_name, $settings );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $userfeedback_settings;
		$userfeedback_settings = $settings;
	}

	return $did_update;
}

/**
 * Helper method for deleting multiple settings value.
 *
 * @since 1.0.0
 * @access public
 *
 * @param string $keys   The settings keys.
 * @return boolean True if removed, false if not.
 */
function userfeedback_delete_options( $keys = array() ) {
	// If no keys, exit
	if ( empty( $keys ) || ! is_array( $keys ) ) {
		return false;
	}

	$option_name = userfeedback_get_option_name();

	// First let's grab the current settings
	$settings = userfeedback_get_options();

	// Next let's try to remove the keys
	foreach ( $keys as $key ) {
		if ( isset( $settings[ $key ] ) ) {
			unset( $settings[ $key ] );
		}
	}

	$did_update = update_option( $option_name, $settings );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $userfeedback_settings;
		$userfeedback_settings = $settings;
	}

	return $did_update;
}

function userfeedback_export_settings() {
	$settings = userfeedback_get_options();
	$exclude  = array();

	foreach ( $exclude as $e ) {
		if ( ! empty( $settings[ $e ] ) ) {
			unset( $settings[ $e ] );
		}
	}
	return wp_json_encode( $settings );
}

/**
 * Helper method for getting the license information.
 *
 * @since 1.0.0
 * @access public
 * @return string       The value of the setting.
 */
function userfeedback_get_license() {
	$license = UserFeedback()->license->get_site_license();
	$license = $license ? $license : UserFeedback()->license->get_network_license();
	$default = UserFeedback()->license->get_default_license_key();
	if ( empty( $license ) && ! empty( $default ) ) {
		$license        = array();
		$license['key'] = UserFeedback()->license->get_default_license_key();
	}
	return $license;
}

/**
 * Helper method for getting the license key.
 *
 * @since 1.0.0
 * @access public
 * @return string       The value of the setting.
 */
function userfeedback_get_license_key() {
	if ( userfeedback_is_pro_version() ) {
		return UserFeedback()->license->get_license_key();
	}
	return '';
}
