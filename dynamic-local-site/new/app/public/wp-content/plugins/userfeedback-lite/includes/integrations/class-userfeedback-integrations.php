<?php

/**
 * Integrations class.
 *
 * Loads all available integrations
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author  David Paternina
 */
class UserFeedback_Integrations {

	/**
	 * Loads integrations and registers them
	 */
	public function __construct() {
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-plugin-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-monsterinsights-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-exactmetrics-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-wp-smtp-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-aioseo-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-userfeedback-uncanny-integration.php';
	}
}
