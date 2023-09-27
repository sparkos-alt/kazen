<?php

/**
 * UserFeedback Onboarding Wizard
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class UserFeedback_Onboarding_Wizard {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'maybe_load_onboarding_wizard' ) );
		add_action( 'admin_menu', array( $this, 'add_dashboard_page' ) );
		add_action( 'network_admin_menu', array( $this, 'add_dashboard_page' ) );
	}

	/**
	 * Register page through WordPress's hooks.
	 */
	public function add_dashboard_page() {
		add_dashboard_page(
			'',
			'',
			'userfeedback_save_settings',
			'userfeedback_onboarding',
			''
		);
	}

	/**
	 * Checks if the Wizard should be loaded in current context.
	 */
	public function maybe_load_onboarding_wizard() {

		// Check for wizard-specific parameter
		// Allow plugins to disable the onboarding wizard
		// Check if current user is allowed to save settings.
		if ( ! (
			isset( $_GET['page'] ) ||
			'userfeedback_onboarding' !== $_GET['page'] ||
			apply_filters( 'userfeedback_enable_onboarding_wizard', true ) ||
			! current_user_can( 'userfeedback_save_settings' )
		) ) {
			return;
		}

		// Don't load the interface if doing an ajax call.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		set_current_screen();

		// Remove an action in the Gutenberg plugin ( not core Gutenberg ) which throws an error.
		remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );

		$this->load_onboarding_wizard();
	}

	/**
	 * Load the Onboarding Wizard template.
	 */
	private function load_onboarding_wizard() {

		$this->enqueue_scripts();

		$this->onboarding_wizard_header();
		$this->onboarding_wizard_content();
		$this->onboarding_wizard_footer();

		exit;
	}

	private function enqueue_scripts() {
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

		// -----------------------------------------------------
		// --------------- Onboarding scripts ------------------
		wp_register_script(
			'userfeedback-vue-onboarding-script',
			userfeedback_get_admin_asset_url( '/assets/vue/js/onboarding.js' ),
			apply_filters(
				'userfeedback_onboarding_script_dependencies',
				array(
					'userfeedback-vue-chunk-vendors',
					'userfeedback-vue-chunk-common',
				)
			),
			userfeedback_get_asset_version(),
			true
		);
		wp_enqueue_script( 'userfeedback-vue-onboarding-script' );
		wp_localize_script(
			'userfeedback-vue-onboarding-script',
			'userfeedback',
			userfeedback_get_common_script_localization_object()
		);

		// Styles
		wp_enqueue_style(
			'userfeedback-vue-onboarding',
			userfeedback_get_admin_asset_url( '/assets/vue/css/onboarding.css' ),
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

		$this->remove_conflicting_asset_files();
	}

	/**
	 * Outputs the simplified header used for the Onboarding Wizard.
	 */
	public function onboarding_wizard_header() {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width"/>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<title><?php esc_html_e( 'UserFeedback &rsaquo; Onboarding Wizard', 'userfeedback' ); ?></title>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_print_scripts' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>
			<body class="user-feedback">
		<?php
	}

	/**
	 * Outputs the content of the current step.
	 */
	public function onboarding_wizard_content() {
		echo '<div id="userfeedback-onboarding"></div>';
	}

	/**
	 * Outputs the simplified footer used for the Onboarding Wizard.
	 */
	public function onboarding_wizard_footer() {
		?>
		<?php wp_print_scripts( 'userfeedback-vue-onboarding-script' ); ?>
		</body>
		</html>
		<?php
	}

	/**
	 * Remove assets added by other plugins which conflict.
	 */
	public function remove_conflicting_asset_files() {
		$scripts = array(
			'jetpack-onboarding-vendor', // Jetpack Onboarding Bluehost.
		);

		if ( ! empty( $scripts ) ) {
			foreach ( $scripts as $script ) {
				wp_dequeue_script( $script ); // Remove JS file.
				wp_deregister_script( $script );
			}
		}
	}
}

new UserFeedback_Onboarding_Wizard();
