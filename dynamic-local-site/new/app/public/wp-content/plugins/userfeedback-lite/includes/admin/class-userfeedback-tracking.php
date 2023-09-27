<?php
/**
 * Tracking functions for reporting plugin usage to the UserFeedback site for users that have opted in
 *
 * @package     UserFeedback
 * @subpackage  Admin
 * @copyright   Copyright (c) 2018, Chris Christoff
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Usage tracking
 *
 * @access public
 * @since  7.0.0
 * @return void
 */
class UserFeedback_Tracking {

	public function __construct() {
		add_action( 'init', array( $this, 'schedule_send' ) );
		add_action( 'userfeedback_settings_save_general_end', array( $this, 'check_for_settings_optin' ) );
		add_action( 'admin_head', array( $this, 'check_for_optin' ) );
		add_action( 'admin_head', array( $this, 'check_for_optout' ) );
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( 'userfeedback_usage_tracking_cron', array( $this, 'send_checkin' ) );
	}

	private function get_data() {
		$data = array();

		// Retrieve current theme info
		$theme_data = wp_get_theme();

		// Specific UF data
		$surveys_count = UserFeedback_Survey::count();
		$tracked_data  = get_option( 'userfeedback_tracking_data', array() );

		$settings = array_merge( userfeedback_get_options(), $tracked_data );

		$count_b = 1;
		if ( is_multisite() ) {
			if ( function_exists( 'get_blog_count' ) ) {
				$count_b = get_blog_count();
			} else {
				$count_b = 'Not Set';
			}
		}

		$data['php_version']    = phpversion();
		$data['uf_version']     = USERFEEDBACK_VERSION;
		$data['wp_version']     = get_bloginfo( 'version' );
		$data['server']         = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$data['over_time']      = get_option( 'userfeedback_over_time', array() );
		$data['multisite']      = is_multisite();
		$data['url']            = home_url();
		$data['themename']      = $theme_data->Name;
		$data['themeversion']   = $theme_data->Version;
		$data['email']          = get_bloginfo( 'admin_email' );
		$data['key']            = userfeedback_get_license_key();
		$data['sas']            = userfeedback_get_shareasale_id();
		$data['settings']       = $settings;
		$data['surveys_count']  = $surveys_count;
		$data['pro']            = (int) userfeedback_is_pro_version();
		$data['sites']          = $count_b;
		$data['usagetracking']  = get_option( 'userfeedback_usage_tracking_config', false );
		$data['usercount']      = function_exists( 'get_user_count' ) ? get_user_count() : 'Not Set';
		$data['timezoneoffset'] = date( 'P' );

		// Retrieve current plugin information
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $key ] );
			}
		}

		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = $plugins;
		$data['locale']           = get_locale();

		return $data;
	}

	public function send_checkin( $override = false, $ignore_last_checkin = false ) {

		$home_url       = trailingslashit( home_url() );
		$debug_tracking = get_option( 'userfeedback_debug_usage_tracking', false );

		if ( ! $debug_tracking ) {
			if ( strpos( $home_url, 'userfeedback.com' ) !== false ) {
				return false;
			}

			if ( ! userfeedback_is_tracking_allowed() && ! $override ) {
				return false;
			}

			// Send a maximum of once per week
			$last_send = get_option( 'userfeedback_usage_tracking_last_checkin' );
			if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
				return false;
			}
		}

		$usage_tracking_url = apply_filters( 'userfeedback_usage_tracking_url', 'https://miusage.com/v1/uf-checkin/' );

		$params = apply_filters(
			'userfeedback_usage_tracking_params',
			array(
				'method'      => 'POST',
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.1',
				'body'        => $this->get_data(),
				'user-agent'  => 'UF/' . USERFEEDBACK_VERSION . '; ' . get_bloginfo( 'url' ),
			)
		);

		wp_remote_post( $usage_tracking_url, $params );

		// If we have completed successfully, recheck in 1 week
		update_option( 'userfeedback_usage_tracking_last_checkin', time() );
		// Clear weekly tracked data
		update_option( 'userfeedback_tracking_data', array() );
		return true;
	}

	public function schedule_send() {
		if ( ! wp_next_scheduled( 'userfeedback_usage_tracking_cron' ) ) {
			$tracking             = array();
			$tracking['day']      = rand( 0, 6 );
			$tracking['hour']     = rand( 0, 23 );
			$tracking['minute']   = rand( 0, 59 );
			$tracking['second']   = rand( 0, 59 );
			$tracking['offset']   = ( $tracking['day'] * DAY_IN_SECONDS ) +
									( $tracking['hour'] * HOUR_IN_SECONDS ) +
									( $tracking['minute'] * MINUTE_IN_SECONDS ) +
									 $tracking['second'];
			$tracking['initsend'] = strtotime( 'next sunday' ) + $tracking['offset'];

			wp_schedule_event( $tracking['initsend'], 'weekly', 'userfeedback_usage_tracking_cron' );
			update_option( 'userfeedback_usage_tracking_config', $tracking );
		}
	}

	public function check_for_settings_optin() {
		if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
			return;
		}

		if ( userfeedback_is_pro_version() ) {
			return;
		}

		// Send an initial check in on settings save
		$post_data      = sanitize_post( $_POST, 'raw' );
		$anonymous_data = isset( $post_data['anonymous_data'] ) ? 1 : 0;
		if ( $anonymous_data ) {
			$this->send_checkin( true, true );
		}

	}

	public function check_for_optin() {
		if ( ! ( ! empty( $_REQUEST['uf_action'] ) && 'opt_into_tracking' === $_REQUEST['uf_action'] ) ) {
			return;
		}

		if ( userfeedback_get_option( 'anonymous_data', false ) ) {
			return;
		}

		if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
			return;
		}

		if ( userfeedback_is_pro_version() ) {
			return;
		}

		userfeedback_update_option( 'anonymous_data', 1 );
		$this->send_checkin( true, true );
	}

	public function check_for_optout() {
		if ( ! ( ! empty( $_REQUEST['uf_action'] ) && 'opt_out_of_tracking' === $_REQUEST['uf_action'] ) ) {
			return;
		}

		if ( userfeedback_get_option( 'anonymous_data', false ) ) {
			return;
		}

		if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
			return;
		}

		if ( userfeedback_is_pro_version() ) {
			return;
		}

		userfeedback_update_option( 'anonymous_data', 0 );
	}

	public function add_schedules( $schedules = array() ) {
		// Adds once weekly to the existing schedules.
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'userfeedback' ),
		);
		return $schedules;
	}
}
new UserFeedback_Tracking();
