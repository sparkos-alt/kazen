<?php

/**
 * Base plugin integration class.
 *
 * Provides a standard way of registering integrations with other plugins.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
abstract class UserFeedback_Plugin_Integration {

	/**
	 * Plugin slug. Used to validate if plugin is installed and active
	 *
	 * @var
	 */
	protected static $slug;

	/**
	 * Integration name.
	 * Used as key in the integrations object sent to the frontend.
	 *
	 * @var
	 */
	protected $name;

	/**
	 * Whether to add to the integrations object exposed to the admin scripts
	 *
	 * @var bool
	 */
	protected $load_in_admin_scripts = true;

	/**
	 * Whether to add to the integrations object exposed to the frontend scripts
	 *
	 * @var bool
	 */
	protected $load_in_frontend_scripts = false;

	public function __construct() {
		if ( $this->load_in_admin_scripts ) {
			add_filter( 'userfeedback_admin_script_localization', array( $this, 'register_integration' ) );
		}

		if ( $this->load_in_frontend_scripts ) {
			add_filter( 'userfeedback_frontend_script_localization', array( $this, 'register_integration' ) );
		}
	}

	/**
	 * Get integration data exposed to frontend/admin scripts
	 *
	 * @return array
	 */
	protected function get_integration_data() {
		/**
		 * @var $called_class UserFeedback_Plugin_Integration
		 */
		$called_class = get_called_class();
		return array(
			'is_active'    => class_exists( $called_class ) && $called_class::is_active(),
			'is_installed' => class_exists( $called_class ) && $called_class::is_installed(),
			'basename'     => class_exists( $called_class ) ? $called_class::get_basename() : null,
		);
	}

	/**
	 * Add integration data to frontend/admin scripts
	 *
	 * @param $data
	 * @return array
	 */
	public function register_integration( $data ) {
		$data['integrations'][ $this->name ] = $this->get_integration_data();
		return $data;
	}

	/**
	 * Check if the plugin is installed
	 *
	 * @return bool
	 */
	public static function is_installed() {
		/**
		 * @var $called_class UserFeedback_Plugin_Integration
		 */
		$called_class = get_called_class();
		return userfeedback_is_plugin_installed( $called_class::$slug );
	}

	/**
	 * Check if the plugin is active
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		/**
		 * @var $called_class UserFeedback_Plugin_Integration
		 */
		$called_class = get_called_class();
		return is_plugin_active( $called_class::$slug );
	}

	/**
	 * Get plugin base name
	 *
	 * @return mixed
	 */
	abstract public function get_basename();
}
