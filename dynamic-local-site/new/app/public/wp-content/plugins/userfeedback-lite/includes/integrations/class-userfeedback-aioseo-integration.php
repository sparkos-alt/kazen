<?php

class UserFeedback_Aioseo_Integration extends UserFeedback_Plugin_Integration {

	/**
	 * Plugin slugs to check if either Lite or Pro exist
	 */
	protected static $slugs = [
		'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'all-in-one-seo-pack-pro/all_in_one_seo_pack.php'
	];

	/**
	 * @inheritdoc
	 */
	protected $name = 'all-in-one-seo-pack';

	
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
		return userfeedback_is_plugin_installed( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_basename() {
		return 'all-in-one-seo-pack/all_in_one_seo_pack.php';
	}
}

new UserFeedback_Aioseo_Integration();
