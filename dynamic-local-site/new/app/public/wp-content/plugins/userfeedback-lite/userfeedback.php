<?php

/**
 * Plugin Name: UserFeedback Lite
 * Plugin URI: https://www.userfeedback.com/lite?utm_source=liteplugin&utm_medium=pluginlist
 * Description: See what your analytics software isn’t telling you with powerful UserFeedback surveys.
 * Author: UserFeedback Team
 * Version: 1.0.10
 * Requires PHP: 5.6
 * Requires at least: 5.9.0
 * Author URI: https://userfeedback.com/lite
 * Text Domain: userfeedback
 * Domain Path: /languages
 *
 * @category            Plugin
 * @copyright           Copyright © 2022 UserFeedback
 * @author              David Paternina
 * @package             UserFeedback
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'userfeedback-base.php';

/**
 * Main plugin class
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 * @author David Paternina
 * @access public
 */
final class UserFeedback_Lite extends UserFeedback_Base {


	/**
	 * @inheritdoc
	 */
	public $plugin_name = 'UserFeedback Lite';

	/**
	 * @inheritdoc
	 */
	public $plugin_slug = 'userfeedback-lite';

	/**
	 *
	 */
	public function __construct() {
		$this->file = __FILE__;
		add_filter( 'userfeedback_vue_notices', array( $this, 'add_vue_notices' ) );
		add_filter( 'userfeedback_vue_wp_notices', array( $this, 'add_vue_wp_notices' ) );
		add_action( 'wp_logout', array( $this, 'handle_user_logout' ) );

		// Admin footer text.
		add_filter( 'admin_footer_text', [ $this, 'admin_footer' ], 1, 2 );
	}

	/**
	 * Run tasks before user is logged out.
	 *
	 * @param $notices
	 * @return mixed
	 */
	public function handle_user_logout( $user_id ) {
		$upgrade_notice_id = 'lite-upgrade-prompt';
		delete_user_meta( $user_id, userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id );
	}

	/**
	 * Add Vue notices for Lite plugin
	 *
	 * @param $notices
	 * @return mixed
	 */
	public function add_vue_notices( $notices ) {

		// Lite Upgrade prompt
		$upgrade_notice_id = 'lite-upgrade-prompt';
		$user_upgrade_prompt = get_user_meta( get_current_user_id(), userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id, true );
		// $show_upgrade_prompt = get_option( userfeedback_get_notice_hide_opt_prefix() . $upgrade_notice_id, 0 );
		$show_upgrade_prompt = ( $user_upgrade_prompt == '' ) ? 0 : $user_upgrade_prompt;

		if ( time() - $show_upgrade_prompt > 14 * DAY_IN_SECONDS ) {
			$show_upgrade_prompt = true;
		} else {
			$show_upgrade_prompt = false;
		}

		if ( $show_upgrade_prompt ) {
			$upgrade_link = userfeedback_get_upgrade_link( 'floatbar', 'upgrade' );
			$notices[]    = array(
				'id'      => $upgrade_notice_id,
				'content' =>
				sprintf(
					__( "%1\$sYou're using UserFeedback Lite%2\$s. To unlock all our features, consider %3\$supgrading to PRO%4\$s for 50&#37; off.", 'userfeedback' ),
					'<b>',
					'</b>',
					'<a target="_blank" rel="noopener" href="' . $upgrade_link . '">',
					'</a>'
				),
				'icon'    => 'exclamation-circle'
			);
		}

		return $notices;
	}
	
	/**
	 * Add Vue Wp notices
	 *
	 * @param $notices
	 * @return mixed
	 */
	public function add_vue_wp_notices( $notices ) {
		
		$notices = [];
		// show complete onboarding notice
		$onboarding_step = userfeedback_get_option( 'userfeedback_onboarding_step', false );
		$surveys = UserFeedback_Survey::all();

		$button_text = __('Launch Wizard', 'userfeedback');
		$button_link = admin_url('admin.php?page=userfeedback_onboarding');

		if( 'install-complete' === $onboarding_step && empty($surveys) ) {
			$button_text = __('Create Survey', 'userfeedback');
			$button_link = admin_url('admin.php?page=userfeedback_surveys#/new');
		}

		if( 'install-complete' !== $onboarding_step || empty($surveys) ) {
			$notices[] = array(
				'id' => 'complete-onboarding',
				'content' => sprintf(
					__('<p><strong>Welcome to UserFeedback!</strong><br>Thanks for installing UserFeedback to collect real user feedback from your website visitors. Now that you’ve installed our plugin, you’re less than 2 minutes away from launching a survey.</p>
						<p><a href="%s" class="button user-feedback-button" rel="noopener">%s</a></p>', 'userfeedback'), 
						$button_link, 
						$button_text
					),
				'dismissable' => false,
				'type' => 'info'
			);
		}

		foreach ( $notices as $key => $notice ) {
			if ( ! empty( $notice['id'] ) ) {
				$hide_notice = get_user_meta( get_current_user_id(), userfeedback_get_wp_notice_hide_opt_prefix() . $notice['id'], true );
				if ( $hide_notice ) {
					unset( $notices[ $key ] );
				}
			}
		}

		return $notices;
	}

	/**
	 * @inheritdoc
	 */
	public function define_globals() {
		parent::define_globals();

		if ( ! defined( 'USERFEEDBACK_LITE_VERSION' ) ) {
			define( 'USERFEEDBACK_LITE_VERSION', USERFEEDBACK_VERSION );
		}
	}

	/**
	 * @inheritdoc
	 */
	public function check_for_dual_installation() {
		// Detect Pro version and return early
		if ( class_exists( 'UserFeedback_Pro' ) ) {
			add_action( 'admin_notices', array( self::$instance, 'userfeedback_pro_notice' ) );
			return true;
		}
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function load_instance_properties() {

		if ( is_admin() ) {
			require_once USERFEEDBACK_PLUGIN_DIR . 'lite/includes/admin/class-userfeedback-connect.php';
		}
	}

	/**
	 * @inheritdoc
	 */
	public function define_instance_globals() {
	}

	/**
	 * Output a nag notice if the user has both Lite and Pro activated
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return  void
	 */
	public function userfeedback_pro_notice() {
		$url = admin_url( 'plugins.php' );
		// Check for MS dashboard
		if ( is_network_admin() ) {
			$url = network_admin_url( 'plugins.php' );
		}
		?>
		<div class="error">
			<p><?php echo sprintf( esc_html__( 'Please %1$suninstall%2$s the UserFeedback Lite Plugin. Your Pro version of UserFeedback may not work as expected until the Lite version is uninstalled.', 'userfeedback' ), '<a href="' . esc_url_raw($url) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}

	/**
	 * When user is on a UserFeedback related admin page, display footer text
	 * that graciously asks them to rate us.
	 *
	 * @since 1.0.5
	 *
	 * @param string $text Footer text.
	 *
	 * @return string
	 */
	public function admin_footer($text){
		global $current_screen;
		if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'userfeedback' ) !== false ) {
			$url  = 'https://wordpress.org/support/plugin/userfeedback-lite/reviews/?filter=5#new-post';
			$text = sprintf(
				wp_kses( /* translators: $1$s - UserFeedback plugin name; $2$s - WP.org review link; $3$s - WP.org review link. */
					__( 'Please rate %1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%3$s" target="_blank" rel="noopener">WordPress.org</a> to help us spread the word.', 'userfeedback' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				'<strong>UserFeedback</strong>',
				$url,
				$url
			);
		}

		return $text;
	}

}

/**
 * Fired when the plugin is activated.
 *
 * @access public
 * @since 1.0.0
 *
 * @global int $wp_version      The version of WordPress for this install.
 * @global object $wpdb         The WordPress database object.
 * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false otherwise.
 *
 * @return void
 */
function userfeedback_lite_activation_hook( $network_wide ) {
	$url = admin_url( 'plugins.php' );
	// Check for MS dashboard
	if ( is_network_admin() ) {
		$url = network_admin_url( 'plugins.php' );
	}

	if ( class_exists( 'UserFeedback' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( sprintf( esc_html__( 'Please uninstall and remove UserFeedback Pro before activating UserFeedback Lite. The Lite version has not been activated. %1$sClick here to return to the Dashboard%2$s.', 'userfeedback' ), '<a href="' . esc_url_raw($url) . '">', '</a>' ) );
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-userfeedback-compatibility-check.php';
	$compatibility = UserFeedback_Compatibility_Check::get_instance();
	$compatibility->maybe_deactivate_plugin( plugin_basename( __FILE__ ) );

	// Add transient to trigger redirect.
	set_transient( '_userfeedback_activation_redirect', 1, 30 );
}
register_activation_hook( __FILE__, 'userfeedback_lite_activation_hook' );

/**
 * Fired when the plugin is uninstalled.
 *
 * @access public
 * @since 1.0.0
 *
 * @return  void
 */
function userfeedback_uninstall_hook() {
	wp_cache_flush();
	$instance = UserFeedback();

	// Don't delete any data if the PRO version is already active.
	if ( userfeedback_is_pro_version() ) {
		return;
	}

	// Remove email summaries cron jobs.
	wp_clear_scheduled_hook( 'userfeedback_email_summaries_cron' );

	// Delete the notifications data.
	$instance->notifications->delete_notifications_data();

	// Delete other options.
	userfeedback_uninstall_remove_options();
}
register_uninstall_hook( __FILE__, 'userfeedback_uninstall_hook' );

/**
 * The main function responsible for returning the one true UserFeedback_Lite
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $userfeedback = UserFeedback_Lite(); ?>
 *
 * @since 1.0.0
 *
 * @uses UserFeedback_Lite::get_instance() Retrieve UserFeedback_Lite instance.
 *
 * @return UserFeedback_Lite The singleton UserFeedback_Lite instance.
 */
function UserFeedback_Lite() {
	return UserFeedback_Lite::get_instance();
}
