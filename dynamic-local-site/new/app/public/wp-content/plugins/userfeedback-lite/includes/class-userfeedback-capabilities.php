<?php

/**
 * Capabilities class.
 *
 * @access public
 * @since 1.0.0
 *
 * @package UserFeedback
 * @subpackage Capabilities
 * @author  David Paternina
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserFeedback_Capabilities {

	/**
	 * Map of settings names with actual capabilities names
	 *
	 * @var string[]
	 */
	static $caps = array(
		'create_edit_surveys' => 'userfeedback_create_edit_surveys',
		'view_results'        => 'userfeedback_view_results',
		'delete_surveys'      => 'userfeedback_delete_surveys',
		'save_settings'       => 'userfeedback_save_settings',
	);

	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'userfeedback_add_capabilities' ), 10, 4 );
	}

	/**
	 * Check capability from save settings
	 *
	 * @param $permission_key
	 * @param $user_id
	 * @return array|string[]
	 */
	private function userfeedback_check_user_permissions_from_settings( $permission_key, $user_id ) {

		$roles = userfeedback_get_option( $permission_key, array() );

		$user_can_via_settings = false;
		if ( ! empty( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( is_string( $role ) ) {
					if ( user_can( $user_id, $role ) ) {
						$user_can_via_settings = true;
						break;
					}
				}
			}
		}

		if ( user_can( $user_id, 'manage_options' ) || $user_can_via_settings ) {
			return array();
		}

		return array( 'manage_options' );
	}

	/**
	 * Map UserFeedback Capabilities.
	 *
	 * Using meta caps, we're creating virtual capabilities that are
	 * for backwards compatibility reasons given to users with manage_options, and to
	 * users who have at least of the roles selected in the options on the permissions
	 * tab of the UserFeedback settings.
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @param array  $caps Array of capabilities the user has.
	 * @param string $cap The current cap being filtered.
	 * @param int    $user_id User to check permissions for.
	 * @param array  $args Extra parameters. Unused.
	 * @return array Array of caps needed to have this meta cap. If returned array is empty, user has the capability.
	 */
	public function userfeedback_add_capabilities( $caps, $cap, $user_id, $args ) {

		$found_setting_key = array_search( $cap, self::$caps );

		if ( $found_setting_key ) {
			return $this->userfeedback_check_user_permissions_from_settings(
				$found_setting_key,
				$user_id
			);
		}

		return $caps;
	}
}

new UserFeedback_Capabilities();
