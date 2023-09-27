<?php

/**
 * Addons class.
 *
 * @since 1.0.0
 *
 * @package UserFeedback
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieves addons from the stored transient or remote server.
 *
 * @since 1.0.0
 *
 * @return bool | array    false | Array of licensed and unlicensed Addons.
 */
function userfeedback_get_addons() {
	// Get license key and type.
	$key  = '';
	$type = 'lite';
	if ( userfeedback_is_pro_version() ) {
		$key  = is_network_admin() ? UserFeedback()->license->get_network_license_key() : UserFeedback()->license->get_site_license_key();
		$type = is_network_admin() ? UserFeedback()->license->get_network_license_type() : UserFeedback()->license->get_site_license_type();
	}

	// Get addons data from transient or perform API query if no transient.
	if ( false === ( $addons = get_transient( '_userfeedback_addons' ) ) ) {
		$addons = userfeedback_get_addons_data( $key );
	}
	// If no Addons exist, return false
	if ( ! $addons ) {
		return false;
	}

	// Iterate through Addons, to build two arrays:
	// - Addons the user is licensed to use,
	// - Addons the user isn't licensed to use.
	$results = array(
		'licensed'   => array(),
		'unlicensed' => array(),
	);
	foreach ( (array) $addons as $i => $addon ) {
		// Determine whether the user is licensed to use this Addon or not.
		if ( empty( $type ) ) {
			$results['unlicensed'][] = $addon;
		} elseif (
			( in_array( 'Plus', $addon->categories ) || in_array( 'Pro', $addon->categories ) ) &&
			( $type == 'pro' || $type == 'elite' )
		) {
			$results['licensed'][] = $addon;
		} elseif ( in_array( 'Plus', $addon->categories ) && $type == 'plus' ) {
			$results['licensed'][] = $addon;
		} else {
			$results['unlicensed'][] = $addon;
		}
	}

	// Return Addons, split by licensed and unlicensed.
	return $results;
}

/**
 * Pings the remote server for addons data.
 *
 * @since 1.0.0
 *
 * @param   string $key    The user license key.
 * @return  array|boolean Array of addon data otherwise or false if error
 */
function userfeedback_get_addons_data( $key ) {
	// Get Addons
	// If the key is valid, we'll get personalised upgrade URLs for each Addon (if necessary) and plugin update information.
	if ( userfeedback_is_pro_version() && $key ) {
		$addons = UserFeedback()->license_actions->perform_remote_request( 'get-addons-data', array( 'tgm-updater-key' => $key ) );
	} else {
		$addons = userfeedback_get_all_addons_data();
	}

	// If there was an API error, set transient for only 10 minutes.
	if ( ! $addons ) {
		set_transient( '_userfeedback_addons', false, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	// If there was an error retrieving the addons, set the error.
	if ( isset( $addons->error ) ) {
		set_transient( '_userfeedback_addons', false, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	// Otherwise, our request worked. Save the data and return it.
	set_transient( '_userfeedback_addons', $addons, 4 * HOUR_IN_SECONDS );
	return $addons;
}

/**
 * Get all addons without a license, for lite users.
 *
 * @return array|bool|mixed|object
 */
function userfeedback_get_all_addons_data() {
	// Build the body of the request.
	$body = array(
		'tgm-updater-action'     => 'get-all-addons-data',
		'tgm-updater-key'        => '',
		'tgm-updater-wp-version' => get_bloginfo( 'version' ),
		'tgm-updater-referer'    => site_url(),
		'tgm-updater-mi-version' => USERFEEDBACK_VERSION,
		'tgm-updater-is-pro'     => false,
	);
	$body = http_build_query( $body, '', '&' );

	// Build the headers of the request.
	$headers = array(
		'Content-Type'   => 'application/x-www-form-urlencoded',
		'Content-Length' => strlen( $body ),
	);

	// Setup variable for wp_remote_post.
	$post = array(
		'headers' => $headers,
		'body'    => $body,
	);

	// Perform the query and retrieve the response.
	$response      = wp_remote_post( userfeedback_get_licensing_url(), $post );
	$response_code = wp_remote_retrieve_response_code( $response );
	$response_body = wp_remote_retrieve_body( $response );

	// Bail out early if there are any errors.
	if ( 200 !== $response_code || is_wp_error( $response_body ) ) {
		return false;
	}

	// Return the json decoded content.
	return json_decode( $response_body );
}

/**
 * Retrieve the plugin basename from the plugin slug.
 *
 * @since 1.0.0
 *
 * @param string $slug The plugin slug.
 * @return string      The plugin basename if found, else the plugin slug.
 */
function userfeedback_get_plugin_basename_from_slug( $slug ) {
	$keys = array_keys( get_plugins() );

	foreach ( $keys as $key ) {
		if ( preg_match( '|^' . $slug . '|', $key ) ) {
			return $key;
		}
	}

	return $slug;
}

/**
 * Deactivates a UserFeedback addon.
 *
 * @access public
 * @since 1.0.0
 */
function userfeedback_ajax_deactivate_addon() {
	// Run a security check first.
	check_ajax_referer( 'userfeedback-deactivate', 'nonce' );

	if ( ! current_user_can( 'deactivate_plugins' ) ) {
		wp_send_json(
			array(
				'error' => esc_html__( 'You are not allowed to deactivate plugins', 'userfeedback' ),
			)
		);
	}

	// Deactivate the addon.
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( isset( $post_data['plugin'] ) ) {
		if ( isset( $post_data['isnetwork'] ) && $post_data['isnetwork'] ) {
			deactivate_plugins( $post_data['plugin'], false, true );
		} else {
			deactivate_plugins( $post_data['plugin'] );
		}
	}

	wp_send_json_success();
	wp_die();
}
add_action( 'wp_ajax_userfeedback_deactivate_addon', 'userfeedback_ajax_deactivate_addon' );

/**
 * Installs a UserFeedback addon.
 *
 * @access public
 * @since 1.0.0
 */
function userfeedback_ajax_install_addon() {
	// Run a security check first.
	check_ajax_referer( 'userfeedback-install', 'nonce' );

	if ( ! userfeedback_can_install_plugins() ) {
		wp_send_json(
			array(
				'error' => esc_html__( 'You are not allowed to install plugins', 'userfeedback' ),
			)
		);
	}

	// Install the addon.
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( isset( $post_data['plugin'] ) ) {
		$download_url = esc_url_raw(
			filter_input( INPUT_POST, 'plugin', FILTER_SANITIZE_URL )
		);
		global $hook_suffix;

		// Set the current screen to avoid undefined notices.
		set_current_screen();

		// Prepare variables.
		$method = '';
		$url    = add_query_arg(
			array(
				'page' => 'userfeedback-settings',
			),
			admin_url( 'admin.php' )
		);
		$url    = esc_url( $url );

		// Start output bufferring to catch the filesystem form if credentials are needed.
		ob_start();
		if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, null ) ) ) {
			$form = ob_get_clean();
			echo json_encode( array( 'form' => $form ) );
			wp_die();
		}

		// If we are not authenticated, make it happen now.
		if ( ! WP_Filesystem( $creds ) ) {
			ob_start();
			request_filesystem_credentials( $url, $method, true, false, null );
			$form = ob_get_clean();
			echo json_encode( array( 'form' => $form ) );
			wp_die();
		}

		// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
		userfeedback_require_upgrader( false );

		// Create the plugin upgrader with our custom skin.
		$installer = new Plugin_Upgrader( $skin = new UserFeedback_Skin() );
		$installer->install( $download_url );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();
		if ( $installer->plugin_info() ) {
			$plugin_basename = $installer->plugin_info();
			wp_send_json_success( array( 'plugin' => $plugin_basename ) );
			wp_die();
		}
	}

	// Send back a response.
	wp_send_json_success();
	wp_die();
}
add_action( 'wp_ajax_userfeedback_install_addon', 'userfeedback_ajax_install_addon' );

/**
 * Activates a UserFeedback addon.
 *
 * @access public
 * @since 1.0.0
 */
function userfeedback_ajax_activate_addon() {
	// Run a security check first.
	check_ajax_referer( 'userfeedback-activate', 'nonce' );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json(
			array(
				'error' => esc_html__( 'You are not allowed to activate plugins', 'userfeedback' ),
			)
		);
	}

	// Activate the addon.
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( isset( $post_data['plugin'] ) ) {
		if ( isset( $post_data['isnetwork'] ) && $post_data['isnetwork'] ) {
			$activate = activate_plugin( $post_data['plugin'], null, true );
		} else {
			$activate = activate_plugin( $post_data['plugin'] );
		}
		// Disable MonsterInsights redirect
		if(strstr($post_data['plugin'], 'google-analytics-for-wordpress')){
			delete_transient( '_monsterinsights_activation_redirect' );
		}
		// Disable Exactmetrics redirect
		if(strstr($post_data['plugin'], 'google-analytics-dashboard-for-wp')){
			delete_transient( '_exactmetrics_activation_redirect' );
		}
		// Disable WP Mail SMTP redirect
		if(strstr($post_data['plugin'], 'wp-mail-smtp')){
			delete_transient( 'wp_mail_smtp_activation_redirect' );
		}
		if ( is_wp_error( $activate ) ) {
			echo json_encode( array( 'error' => $activate->get_error_message() ) );
			wp_die();
		}
	}

	wp_send_json_success();
	wp_die();
}
add_action( 'wp_ajax_userfeedback_activate_addon', 'userfeedback_ajax_activate_addon' );

/**
 * Return the state of the addons ( installed, activated )
 */
function userfeedback_ajax_get_addons() {
	check_ajax_referer( 'uf-admin-nonce', 'nonce' );

	if ( ! current_user_can( 'userfeedback_save_settings' ) ) {
		return;
	}
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( isset( $post_data['network'] ) && intval( $post_data['network'] ) > 0 ) {
		define( 'WP_NETWORK_ADMIN', true );
	}

	$parsed_addons = userfeedback_get_parsed_addons();

	wp_send_json( $parsed_addons );
	exit;
}
add_action( 'wp_ajax_userfeedback_get_addons', 'userfeedback_ajax_get_addons' );

function userfeedback_get_parsed_addons() {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$addons_data       = userfeedback_get_addons();
	$parsed_addons     = array();
	$installed_plugins = get_plugins();

	if ( ! is_array( $addons_data ) ) {
		$addons_data = array();
	}
	$license = get_option( 'userfeedback_license', true );
	foreach ( $addons_data as $addons_type => $addons ) {
		foreach ( $addons as $addon ) {
			$slug  = 'userfeedback-' . $addon->slug;
			$addon = userfeedback_parse_addon( $installed_plugins, $addons_type, $addon, $slug, $license );

			$parsed_addons[ $addon->slug ] = $addon;
		}
	}

	$parsed_addons = apply_filters( 'userfeedback_parsed_addons', $parsed_addons );
	/* Update parsed addons data to use in the scripts localization. */
	update_option( 'userfeedback_parsed_addons', $parsed_addons );
	return $parsed_addons;

}

function userfeedback_parse_addon( $installed_plugins, $addons_type, $addon, $slug, $license ) {
	$active          = false;
	$installed       = false;
	$plugin_basename = userfeedback_get_plugin_basename_from_slug( $slug );

	if ( isset( $installed_plugins[ $plugin_basename ] ) ) {
		$installed = true;

		if ( is_multisite() && is_network_admin() ) {
			$active = is_plugin_active_for_network( $plugin_basename );
		} else {
			$active = is_plugin_active( $plugin_basename );
		}
	}
	if ( empty( $addon->url ) ) {
		$addon->url = '';
	}

	$active_version = false;
	if ( $active ) {
		if ( ! empty( $installed_plugins[ $plugin_basename ]['Version'] ) ) {
			$active_version = $installed_plugins[ $plugin_basename ]['Version'];
		}
	}

	if ( isset( $license['type'] ) && in_array( ucwords( $license['type'] ), $addon->categories ) ) {
		$addons_type = 'licensed';
	}

	$addon->type           = $addons_type;
	$addon->installed      = $installed;
	$addon->active_version = $active_version;
	$addon->active         = $active;
	$addon->basename       = $plugin_basename;

	return $addon;
}


/**
 * Deactivate unlicensed addons.
 *
 * @since 1.0.0
 */
function userfeedback_deactivate_unlicensed_addons() {
	$addons            = userfeedback_get_addons();
	$unlicensed_addons = ($addons && is_array( $addons['unlicensed'] )) ? $addons['unlicensed'] : array();
	if ( empty( $unlicensed_addons ) ) {
		return false;
	}
	$deactivate = array();
	foreach ( $unlicensed_addons as $unlicensed_addon ) {
		$deactivate[] = "userfeedback-{$unlicensed_addon->slug}/userfeedback-{$unlicensed_addon->slug}.php";
	}
	deactivate_plugins( $deactivate );
}

add_action( 'admin_init', 'userfeedback_deactivate_unlicensed_addons' );
