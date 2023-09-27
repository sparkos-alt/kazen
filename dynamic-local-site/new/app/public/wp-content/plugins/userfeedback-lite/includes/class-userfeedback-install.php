<?php

/**
 * UserFeedback Installation and Automatic Upgrades.
 *
 * This file handles setting up new
 * UserFeedback installs as well as performing
 * behind the scene upgrades between
 * UserFeedback versions.
 *
 * @package UserFeedback
 * @subpackage Install/Upgrade
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserFeedback_Install {

	/**
	 * UF Settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array $new_settings When the init() function starts, initially
	 *                          contains the original settings. At the end
	 *                          of init() contains the settings to save.
	 */
	public $new_settings = array();

	/**
	 * Install/Upgrade routine.
	 *
	 * This function is what is called to actually install UF data on new installs and to do
	 * behind the scenes upgrades on UF upgrades. If this function contains a bug, the results
	 * can be catastrophic. This function gets the highest priority in all of UF for unit tests.
	 *
	 * @since 6.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function init() {
		;
		// Get a copy of the current UF settings.
		$this->new_settings = get_option( userfeedback_get_option_name() );

		$version       = get_option( 'userfeedback_current_version', false );
		$cache_cleared = false; // have we forced an object cache to be cleared already (so we don't clear it unnecessarily)

		if ( ! $version ) {
			$this->new_install();

			// set db version (Do not increment! See below large comment)
			update_option( 'userfeedback_db_version', '1.0.0' );

			// Clear cache since coming from Yoast
			if ( ! $cache_cleared ) {
				wp_cache_flush();
				$cache_cleared = true;
			}
		} else { // If existing install
			// Future upgrades...

			// -------------------------
			// Do not use. See userfeedback_after_install_routine comment below.
			do_action( 'userfeedback_after_existing_upgrade_routine', $version );
			$version = get_option( 'userfeedback_current_version', $version );
			update_option( 'userfeedback_version_upgraded_from', $version );
		}

		// This hook is used primarily by the Pro version to run some Pro
		// specific install stuff. Please do not use this hook. It is not
		// considered a public hook by UF's dev team and can/will be removed,
		// relocated, and/or altered without warning at any time. You've been warned.
		// As this hook is not for public use, we've intentionally not docbloc'd this
		// hook to avoid developers seeing it future public dev docs.
		do_action( 'userfeedback_after_install_routine', $version );

		// This is the version of UF installed
		update_option( 'userfeedback_current_version', USERFEEDBACK_VERSION );

		// This is where we save UF settings
		update_option( userfeedback_get_option_name(), $this->new_settings );

		/**
		 * Explanation of UserFeedback core options - Adapted from MonsterInsights'
		 *
		 * By now your head is probably spinning trying to figure
		 * out what all of these version options are for. Note, I've abbreviated
		 * "userfeedback" to "uf" in the options names to make this table easier
		 * to read.
		 *
		 * Here's a basic rundown:
		 *
		 * uf_current_version:  This starts with the actual version UF was
		 *                      installed on. We use this version to
		 *                      determine whether or not a site needs
		 *                      to run one of the behind the scenes
		 *                      UF upgrade routines. This version is updated
		 *                      every time a minor or major background upgrade
		 *                      routine is run. Generally lags behind the
		 *                      USERFEEDBACK_VERSION constant by at most a couple minor
		 *                      versions. Never lags behind by 1 major version
		 *                      or more generally.
		 *
		 * uf_db_version:       This is different from uf_current_version.
		 *                      Unlike the former, this is used to determine
		 *                      if a site needs to run a *user* initiated
		 *                      upgrade routine (incremented in MI_Upgrade class). This
		 *                      value is only update when a user initiated
		 *                      upgrade routine is done. Because we do very
		 *                      few user initiated upgrades compared to
		 *                      automatic ones, this version can lag behind by
		 *                      2 or even 3 major versions. Generally contains
		 *                      the current major version.
		 *
		 * uf_settings:         Returned by userfeedback_get_option_name(), this
		 *                      is actually "userfeedback_settings" for both pro
		 *                      and lite version. However we use a helper function to
		 *                      retrieve the option name in case we ever decide down the
		 *                      road to maintain seperate options for the Lite and Pro versions.
		 *                      If you need to access UF's settings directly, (as opposed to our
		 *                      userfeedback_get_option helper which uses the option name helper
		 *                      automatically), you should use this function to get the
		 *                      name of the option to retrieve.
		 *
		 * Therefore you should never increment uf_db_version in this file and always increment uf_current_version.
		 */
	}

	/**
	 * New UserFeedback Install routine.
	 *
	 * This function installs all the default
	 * things on new UF installs.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function new_install() {

		do_action( 'userfeedback_before_install' );

		// Create DB tables

		$this->create_userfeedback_tables();

		// Create options...

		$this->new_settings = userfeedback_get_default_options();

		$is_pro = userfeedback_is_pro_version();

		$data = array(
			'd'                 => class_exists( 'UserFeedback' ),
			'installed_version' => USERFEEDBACK_VERSION,
			'installed_date'    => time(),
			'installed_pro'     => $is_pro ? time() : false,
			'installed_lite'    => $is_pro ? false : time(),
		);

		update_option( 'userfeedback_over_time', $data, false );

		do_action( 'userfeedback_after_install' );
	}

	/**
	 * Create UserFeedback database tables
	 *
	 * @return void
	 */
	private function create_userfeedback_tables() {
		( new UserFeedback_Survey() )->create_table();
		( new UserFeedback_Response() )->create_table();
	}
}
