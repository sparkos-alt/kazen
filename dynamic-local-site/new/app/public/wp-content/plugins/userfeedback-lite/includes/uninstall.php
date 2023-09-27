<?php

/**
 * Remove various options used in the plugin.
 */
function userfeedback_uninstall_remove_options() {

	// Remove usage tracking options.
	delete_option( 'userfeedback_usage_tracking_config' ); // TODO
	delete_option( 'userfeedback_usage_tracking_last_checkin' ); // TODO

	// Remove version options.
	// delete_option( 'userfeedback_db_version' );
	// delete_option( 'userfeedback_version_upgraded_from' );

	// Delete addons transient.
	// delete_transient( 'userfeedback_addons' );TODO
}
