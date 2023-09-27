<?php

/**
 * Settings Controller class.
 *
 * Handles API calls related to UserFeedback Settings
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Settings {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'userfeedback/v1',
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_settings_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => array( $this, 'save_settings_permission_check' ),
			)
		);

		register_rest_route(
			'userfeedback/v1',
			'/addons',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_addons' ),
				'permission_callback' => array( $this, 'save_settings_permission_check' ),
			)
		);
	}

	/**
	 * Checks if current user can fetch UserFeedback settings
	 *
	 * @return bool
	 */
	public function get_settings_permission_check() {
		return current_user_can( 'userfeedback_save_settings' );
	}

	/**
	 * Checks if current user can save UserFeedback settings
	 *
	 * @return bool
	 */
	public function save_settings_permission_check() {
		return current_user_can( 'userfeedback_save_settings' );
	}

	/**
	 * Fetch UserFeedback settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		global $userfeedback_settings;
		return new WP_REST_Response( $userfeedback_settings );
	}

	/**
	 * Save UserFeedback settings
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$settings = $request->get_param( 'settings' );
		$did_save = userfeedback_save_options( $settings );

		if ( $did_save ) {
			return new WP_REST_Response(
				array(
					'success' => true,
				)
			);
		} else {
			return new WP_REST_Response(
				array(
					'success' => false,
				)
			);
		}
	}
}

new UserFeedback_Settings();
