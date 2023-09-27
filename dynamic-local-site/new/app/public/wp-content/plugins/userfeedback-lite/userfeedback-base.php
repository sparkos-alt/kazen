<?php

if (!class_exists('UserFeedback_Base')) {
	abstract class UserFeedback_Base
	{

		/**
		 * Holds the class object
		 *
		 * @since 1.0.0
		 * @access public
		 * @var UserFeedback_Base Instance of UserFeedback class
		 */
		public static $instance;

		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string $version Plugin version
		 */
		public $version = '1.0.10';

		/**
		 * Plugin file.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string $file PHP File constant for main file.
		 */
		public $file;

		/**
		 * The name of the plugin.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string $plugin_name Plugin name.
		 */
		public $plugin_name;

		/**
		 * Unique plugin slug identifier.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var string $plugin_slug Plugin slug.
		 */
		public $plugin_slug;

		/**
		 * Holds instance of UserFeedback License class.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var UserFeedback_License $license Instance of License class.
		 */
		public $license;

		/**
		 * Holds instance of UserFeedback Admin Notice class.
		 *
		 * @since 1.0.0
		 * @access public
		 * @var UserFeedback_Admin_Notice $notices Instance of Admin Notice class.
		 */
		public $notices;

		/**
		 * Holds instance of UserFeedback Exports class
		 *
		 * @since 1.0.0
		 * @var UserFeedback_Exports $exports
		 */
		public $exports;

		/**
		 * Holds instance of UserFeedback Integrations class
		 *
		 * @since 1.0.0
		 * @var UserFeedback_Integrations
		 */
		public $integrations;

		/**
		 * Holds instance of UserFeedback Notifications class
		 *
		 * @since 1.0.0
		 * @var UserFeedback_Notifications
		 */
		public $notifications;

		/**
		 * Checks for dual plugin installation (Lite and Pro).
		 * Each type is responsible for implementing its own logic.
		 *
		 * This method must return either true or false, depending on whether both types are installed
		 *
		 * @return boolean
		 */
		abstract public function check_for_dual_installation();

		/**
		 * Load and properties and config on Lite and Pro versions
		 *
		 * @return mixed
		 */
		abstract public function load_instance_properties();

		/**
		 * Define lite/pro specific constants
		 *
		 * @return mixed
		 */
		abstract public function define_instance_globals();

		/**
		 * Check compatibility with PHP and WP, and display notices if necessary
		 *
		 * @return bool
		 * @since 1.0.0
		 */
		private function check_compatibility()
		{

			if (defined('USERFEEDBACK_FORCE_ACTIVATION') && USERFEEDBACK_FORCE_ACTIVATION) {
				return true;
			}

			require_once plugin_dir_path(__FILE__) . 'includes/class-userfeedback-compatibility-check.php';
			$compatibility = UserFeedback_Compatibility_Check::get_instance();
			$compatibility->maybe_display_notices();

			return $compatibility->is_php_compatible() && $compatibility->is_wp_compatible();
		}

		/**
		 * @return static
		 */
		public static function get_instance()
		{
			if (!isset(self::$instance)) {
				$current_class = get_called_class();

				self::$instance = new $current_class();

				// Check for presence of both lite and pro plugins, and return early if detected both
				if (self::$instance->check_for_dual_installation()) {
					return self::$instance;
				}

				if (!self::$instance->check_compatibility()) {
					return self::$instance;
				}

				// Define constants
				self::$instance->define_globals();

				// Load in settings
				self::$instance->load_settings();

				// Load in Licensing
				self::$instance->load_licensing();

				// Load files
				self::$instance->require_files();

				// This does the version to version background upgrade routines and initial install
				$uf_version = get_option('userfeedback_current_version', '0.0.0');
				if (version_compare($uf_version, '1.0.0', '<')) {
					add_action('wp_loaded', array(self::$instance, 'install_and_upgrade'));
				}

				if (version_compare($uf_version, '1.0.5', '<')) {
					// Fix database timestamp column.
					add_action('plugins_loaded', array(self::$instance, 'fix_db_timestamp_column'), 15);
				}

				if (is_admin()) {
					// new AM_Deactivation_Survey('UserFeedback', self::$instance->plugin_slug);
				}

				// Load the plugin textdomain.
				add_action('plugins_loaded', array(self::$instance, 'load_plugin_textdomain'), 15);

				

				// Load admin only components.
				if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) {
					self::$instance->notices = new UserFeedback_Admin_Notice();
				}

				self::$instance->integrations  = new UserFeedback_Integrations();
				self::$instance->notifications = new UserFeedback_Notifications();
				self::$instance->load_instance_properties();

				// Run hook to load MonsterInsights addons.
				do_action('userfeedback_load_plugins'); // the updater class for each addon needs to be instantiated via `userfeedback_updater`

				register_uninstall_hook(self::$instance->file, array($current_class, 'uninstall_hook'));
			}

			return self::$instance;
		}

		/**
		 * Define UserFeedback constants.
		 *
		 * This function defines all the UserFeedback PHP constants.
		 *
		 * @return void
		 * @since 1.0.0
		 * @access public
		 */
		public function define_globals()
		{

			if (!defined('USERFEEDBACK_VERSION')) {
				define('USERFEEDBACK_VERSION', $this->version);
			}

			if (!defined('USERFEEDBACK_PLUGIN_NAME')) {
				define('USERFEEDBACK_PLUGIN_NAME', $this->plugin_name);
			}

			if (!defined('USERFEEDBACK_PLUGIN_SLUG')) {
				define('USERFEEDBACK_PLUGIN_SLUG', $this->plugin_slug);
			}

			if (!defined('USERFEEDBACK_PLUGIN_FILE')) {
				define('USERFEEDBACK_PLUGIN_FILE', $this->file);
			}

			if (!defined('USERFEEDBACK_PLUGIN_DIR')) {
				define('USERFEEDBACK_PLUGIN_DIR', plugin_dir_path($this->file));
			}

			if (!defined('USERFEEDBACK_PLUGIN_URL')) {
				define('USERFEEDBACK_PLUGIN_URL', plugin_dir_url($this->file));
			}

			$this->define_instance_globals();
		}

		/**
		 * Loads the plugin textdomain for translation.
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function load_plugin_textdomain()
		{
			$uf_locale = get_locale();
			if (function_exists('get_user_locale')) {
				$uf_locale = get_user_locale();
			}

			// Load Translation files
			// Traditional WordPress plugin locale filter.
			$uf_locale = apply_filters('plugin_locale', $uf_locale, 'userfeedback');
			$uf_mofile = sprintf('%1$s-%2$s.mo', 'userfeedback', $uf_locale);

			// Look for wp-content/languages/userfeedback/userfeedback-{lang}_{country}.mo
			$uf_mofile1 = WP_LANG_DIR . '/userfeedback/' . $uf_mofile;

			// Look in wp-content/languages/plugins/userfeedback/userfeedback-{lang}_{country}.mo
			$uf_mofile2 = WP_LANG_DIR . '/plugins/userfeedback/' . $uf_mofile;

			// Look in wp-content/languages/plugins/userfeedback-{lang}_{country}.mo
			$uf_mofile3 = WP_LANG_DIR . '/plugins/' . $uf_mofile;

			// Look in wp-content/plugins/userfeedback/languages/userfeedback-{lang}_{country}.mo
			$uf_mofile4 = dirname(plugin_basename(USERFEEDBACK_PLUGIN_FILE)) . '/languages/';
			$uf_mofile4 = apply_filters('monsterinsights_pro_languages_directory', $uf_mofile4);

			if (file_exists($uf_mofile1)) {
				load_textdomain('userfeedback', $uf_mofile1);
			} elseif (file_exists($uf_mofile2)) {
				load_textdomain('userfeedback', $uf_mofile2);
			} elseif (file_exists($uf_mofile3)) {
				load_textdomain('userfeedback', $uf_mofile3);
			} else {
				load_plugin_textdomain('userfeedback', false, $uf_mofile4);
			}
		}

		/**
		 * Loads UserFeedback settings
		 *
		 * Adds the items to the base object, and adds the helper functions.
		 *
		 * @return void
		 * @since 1.0.0
		 * @access public
		 */
		public function load_settings()
		{
			global $userfeedback_settings;
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/options.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/helpers.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/logic.php';

			$userfeedback_settings = userfeedback_get_options();
		}

		/**
		 * Loads UserFeedback License
		 *
		 * Loads license class used by UserFeedback
		 *
		 * @return void
		 * @since 1.0.0
		 * @access public
		 */
		public function load_licensing()
		{
			require_once USERFEEDBACK_PLUGIN_DIR . '/includes/class-userfeedback-license.php';
			self::$instance->license = new UserFeedback_License();
		}

		/**
		 * Loads all files into scope.
		 *
		 * @access public
		 * @return    void
		 * @since 6.0.0
		 */
		public function require_files()
		{
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/class-userfeedback-capabilities.php';

			// DB
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/db/class-userfeedback-db.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/db/class-userfeedback-survey.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/db/class-userfeedback-response.php';

			// Survey templates helper
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-templates.php';

			// Survey template parent class
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/survey-templates/class-userfeedback-survey-template.php';

			// ----- Rest Controllers
			// Surveys
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-surveys.php';

			// Results
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-results.php';

			// Settings
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-settings.php';

			// -----

			// Shortcodes
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-shortcodes.php';

			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/common.php';

			// Notifications
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-notifications-runner.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-notification-event.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-notifications.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/notifications/notifications-loader.php';

			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/emails/class-userfeedback-wp-emails.php';
			// We load this one always because the notifications hooks onto the survey response action
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/emails/class-userfeedback-email-response-notification.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/addons.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/plugins.php';

			if (is_admin() || (defined('DOING_CRON') && DOING_CRON)) {

				// Lite and Pro files
				require_once USERFEEDBACK_PLUGIN_DIR . 'assets/lib/pandora/class-am-deactivation-survey.php';
				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-admin-notice.php';
				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/admin.php';
				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/ajax.php';

				// Emails
				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/emails/class-userfeedback-email-summaries.php';

				if (isset($_GET['page']) && 'userfeedback_onboarding' === $_GET['page']) {
					// Only load the Onboarding wizard if the required parameter is present.
					require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-onboarding-wizard.php';
				}

				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-tracking.php';
			}

			if (is_admin()) {
				require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-dashboard-widget.php';
			}

			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/frontend/class-userfeedback-frontend.php';
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/integrations/class-userfeedback-integrations.php';

			// Search results
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-search.php';
			// Logic types
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-logic-type.php';

			// Review
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/admin/class-userfeedback-review.php';
		}

		/**
		 * UserFeedback Install and Updates.
		 *
		 * This function is used install and upgrade UserFeedback. This is used for upgrade routines
		 * that can be done automatically, behind the scenes without the need for user interaction
		 * (for example pagination or user input required), as well as the initial install.
		 *
		 * @return void
		 * @global string $wp_version WordPress version (provided by WordPress core).
		 * @uses UserFeedback::load_settings() Loads UserFeedback settings
		 * @uses UserFeedback_Install::init() Runs upgrade process
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function install_and_upgrade()
		{
			require_once plugin_dir_path($this->file) . 'includes/class-userfeedback-compatibility-check.php';
			$compatibility = UserFeedback_Compatibility_Check::get_instance();

			// If the WordPress site doesn't meet the correct WP or PHP version requirements, don't activate UserFeedback
			if (!$compatibility->is_php_compatible() || !$compatibility->is_wp_compatible()) {
				if (is_plugin_active(plugin_basename($this->file))) {
					return;
				}
			}

			// Don't run if both UF Pro and Lite are installed
			$userFeedback = UserFeedback();
			if ($userFeedback::get_instance()->check_for_dual_installation()) {
				if (is_plugin_active(plugin_basename($this->file))) {
					return;
				}
			}

			// Load settings and globals (so we can use/set them during the upgrade process)
			$userFeedback->define_globals();
			$userFeedback->load_settings();

			// Load upgrade file
			require_once USERFEEDBACK_PLUGIN_DIR . 'includes/class-userfeedback-install.php';

			// Run the UserFeedback upgrade routines
			$updates = new UserFeedback_Install();
			$updates->init();
		}

		/**
		 * Fired when the plugin is uninstalled.
		 *
		 * @access public
		 * @return    void
		 * @since 1.0.0
		 */
		public static function uninstall_hook()
		{
			wp_cache_flush();
			// Note, if both MI Pro and Lite are active, this is an MI Pro instance
			// Therefore MI Lite can only use functions of the instance common to
			// both plugins. If it needs to be pro specific, then include a file that
			// has that method.
			$instance = UserFeedback();

			// If uninstalling via WP-CLI load admin-specific files only here.
			if (defined('WP_CLI') && WP_CLI) {
				define('WP_ADMIN', true);
				$instance->require_files();
				$instance->load_licensing();
				$instance->notices         = new UserFeedback_Admin_Notice();
				$instance->license_actions = new UserFeedback_License_Actions();
			}

			require_once 'includes/uninstall.php';

			// Remove email summaries cron jobs.
			wp_clear_scheduled_hook('userfeedback_email_summaries_cron');

			// Delete the notifications data.
			$instance->notifications->delete_notifications_data();

			// Delete other options.
			userfeedback_uninstall_remove_options();
		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @return void
		 * @since 1.0.0
		 * @access public
		 */
		public function __clone()
		{
			_doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'userfeedback'), '1.0.0');
		}

		/**
		 * Disable unserializing of the class
		 *
		 * Attempting to wakeup an Userfeedback instance will throw a doing it wrong notice.
		 *
		 * @return void
		 * @since 1.0.0
		 * @access public
		 */
		public function __wakeup()
		{
			_doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'userfeedback'), '1.0.0');
		}

		public function fix_db_timestamp_column()
		{
			global $wpdb;
			// only run if admin dashboard
			if (!is_admin()) {
				return;
			}

			// bail if this fix is already applied
			$timestamp_fixed = get_option('userfeedback_timestamp_fixed', false);
			if ($timestamp_fixed) {
				return;
			}
			// get table column details
			$table_fields = $wpdb->get_results("DESCRIBE {$wpdb->prefix}userfeedback_surveys", ARRAY_A);

			if(empty($table_fields)) {
				return;
			}

			foreach ($table_fields as $table_field) {
				if ($table_field['Field'] == 'publish_at') {
					// check extra field has CURRENT_TIMESTAMP on update
					if (strpos($table_field['Extra'], 'CURRENT_TIMESTAMP') > -1) {
						// fix published_at column
						$wpdb->query("ALTER TABLE {$wpdb->prefix}userfeedback_surveys MODIFY publish_at timestamp NULL");
						// fix affected surveys
						$surveys = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}userfeedback_surveys");
						foreach ($surveys as $survey) {
							$wpdb->update(
								"{$wpdb->prefix}userfeedback_surveys",
								array('publish_at' => null),
								array('id' => $survey->id),
								array('%s'),
								array('%d')
							);
						}
					}
				}
			}
			update_option('userfeedback_timestamp_fixed', true);
		}
	}
}

/**
 * Check if we should do any redirect.
 */

if (!function_exists('userfeedback_maybe_redirect_to_onboarding')) {
	function userfeedback_maybe_redirect_to_onboarding()
	{

		// Bail if no activation redirect.
		if (!get_transient('_userfeedback_activation_redirect') || isset($_GET['userfeedback-redirect'])) {
			return;
		}

		// Delete the redirect transient.
		delete_transient('_userfeedback_activation_redirect');

		// Bail if activating from network, or bulk.
		if (isset($_GET['activate-multi'])) { // WPCS: CSRF ok, input var ok.
			return;
		}

		$onboarding = get_option('userfeedback_onboarding_complete', false);

		if (apply_filters('userfeedback_enable_onboarding_wizard', false === $onboarding)) {
			$path = 'index.php?page=userfeedback_onboarding';
			wp_safe_redirect(admin_url($path));
			exit;
		}

		$welcome_screen = userfeedback_get_screen_url('userfeedback_settings', 'general');
		wp_safe_redirect($welcome_screen);
		exit;
	}
}
add_action('admin_init', 'userfeedback_maybe_redirect_to_onboarding', 9999);

/**
 * Returns the UserFeedback combined object that you can use for both
 * UserFeedback Lite and Pro Users. When both plugins active, defers to the
 * more complete Pro object.
 *
 * Warning: Do not use this in Lite or Pro specific code (use the individual objects instead).
 * Also do not use in the UserFeedback Lite/Pro upgrade and install routines.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Prevents the need to do conditional global object logic when you have code that you want to work with
 * both Pro and Lite.
 *
 * Example: <?php $userfeedback = UserFeedback(); ?>
 *
 * @since 6.0.0
 *
 * @uses UserFeedback::get_instance() Retrieve UserFeedback Pro instance.
 * @uses UserFeedback_Lite::get_instance() Retrieve UserFeedback Lite instance.
 *
 * @return UserFeedback The singleton UserFeedback instance.
 */
if (!function_exists('UserFeedback')) {
	function UserFeedback()
	{
		/**
		 * This alone prevents the Lite plugin from being loaded if the Pro version is installed.
		 * Lite variables such as USERFEEDBACK_LITE_VERSION won't be defined if the Pro plugin is installed,
		 */

		return (class_exists('UserFeedback') ? UserFeedback_Pro() : UserFeedback_Lite());
	}
	add_action('plugins_loaded', 'UserFeedback');
}
