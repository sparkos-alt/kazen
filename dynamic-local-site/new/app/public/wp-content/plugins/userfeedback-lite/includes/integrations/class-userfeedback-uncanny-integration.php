<?php

class UserFeedback_Uncanny_Integration extends UserFeedback_Plugin_Integration {

	/**
	 * Plugin slugs to check if either Lite or Pro exist
	 */
	protected static $slugs = [
		'uncanny-automator/uncanny-automator.php',
		'uncanny-automator-pro/uncanny-automator-pro.php'
	];

	/**
	 * @inheritdoc
	 */
	protected $name = 'uncanny-automator';

/**
	 * Override validation function
	 * @inheritdoc
	 */
	public static function is_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( self::$slugs as $slug ) {
			if ( is_plugin_active( $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Override validation function
	 * @inheritdoc
	 */
	public static function is_installed() {
		foreach ( self::$slugs as $slug ) {
			if ( userfeedback_is_plugin_installed( $slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the Pro version.
	 * Doesn't care if plugin is activated, just checks for its presence using the slug
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return userfeedback_is_plugin_installed( 'uncanny-automator-pro/uncanny-automator-pro.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_basename() {
		return 'uncanny-automator/uncanny-automator.php';
	}
}

new UserFeedback_Uncanny_Integration();
