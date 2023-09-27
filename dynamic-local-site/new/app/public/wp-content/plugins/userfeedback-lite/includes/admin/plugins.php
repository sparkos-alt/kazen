<?php

/**
 * Install plugins which are not addons.
 */
function userfeedback_install_plugin()
{
	check_ajax_referer('userfeedback-install', 'nonce');
	$post_data = sanitize_post($_POST, 'raw');
	if (!userfeedback_can_install_plugins()) {
		wp_send_json(
			array(
				'error' => esc_html__('You are not allowed to install plugins', 'userfeedback'),
			)
		);
	}

	$slug = isset($post_data['slug']) ? sanitize_text_field(wp_unslash($post_data['slug'])) : false;

	if (!$slug) {
		wp_send_json(
			array(
				'message' => esc_html__('Missing plugin name.', 'userfeedback'),
			)
		);
	}

	include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	$api = plugins_api(
		'plugin_information',
		array(
			'slug'   => $slug,
			'fields' => array(
				'short_description' => false,
				'sections'          => false,
				'requires'          => false,
				'rating'            => false,
				'ratings'           => false,
				'downloaded'        => false,
				'last_updated'      => false,
				'added'             => false,
				'tags'              => false,
				'compatibility'     => false,
				'homepage'          => false,
				'donate_link'       => false,
			),
		)
	);

	if (is_wp_error($api)) {
		return $api->get_error_message();
	}

	$download_url = $api->download_link;

	$method = '';
	$url    = add_query_arg(
		array(
			'page' => 'userfeedback_settings',
		),
		admin_url('admin.php')
	);
	$url    = esc_url($url);

	ob_start();
	if (false === ($creds = request_filesystem_credentials($url, $method, false, false, null))) {
		$form = ob_get_clean();

		wp_send_json(array('form' => $form));
	}

	// If we are not authenticated, make it happen now.
	if (!WP_Filesystem($creds)) {
		ob_start();
		request_filesystem_credentials($url, $method, true, false, null);
		$form = ob_get_clean();

		wp_send_json(array('form' => $form));
	}

	// We do not need any extra credentials if we have gotten this far, so let's install the plugin.
	userfeedback_require_upgrader();

	// Prevent language upgrade in ajax calls.
	remove_action('upgrader_process_complete', array('Language_Pack_Upgrader', 'async_upgrade'), 20);
	// Create the plugin upgrader with our custom skin.
	$installer = new UserFeedback_Plugin_Upgrader( new UserFeedback_Skin() );
	$installer->install( $download_url );

	// Flush the cache and return the newly installed plugin basename.
	wp_cache_flush();
	wp_send_json_success();
	wp_die();
}
add_action( 'wp_ajax_userfeedback_install_plugin', 'userfeedback_install_plugin' );

function userfeedback_activate_plugin(){
	check_ajax_referer( 'userfeedback-install', 'nonce' );
	$post_data = sanitize_post( $_POST, 'raw' );
	if ( ! userfeedback_can_install_plugins() ) {
		wp_send_json(
			array(
				'error' => esc_html__( 'You are not allowed to install plugins', 'userfeedback' ),
			)
		);
	}

	$basename = isset( $post_data['basename'] ) ? sanitize_text_field( wp_unslash( $post_data['basename'] ) ) : false;

	if ( ! $basename ) {
		wp_send_json(
			array(
				'message' => esc_html__( 'Missing plugin name.', 'userfeedback' ),
			)
		);
	}
	activate_plugin( $basename, '', false, true );
	
	wp_send_json_success();
	wp_die();
}

add_action( 'wp_ajax_userfeedback_activate_plugin', 'userfeedback_activate_plugin' );

/**
 * Get recommended plugins
 */
function userfeedback_get_plugins()
{

	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$installed_plugins = get_plugins();

	$plugins = array();

	// MonsterInsights
	$plugins['monsterinsights'] = array(
		'active'    => function_exists('MonsterInsights'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-mi.png',
		'title'     => 'MonsterInsights',
		'excerpt'   => __('The best Google Analytics plugin for WordPress. See how visitors find and use your website, so you can keep them coming back.', 'userfeedback'),
		'installed' => array_key_exists('google-analytics-for-wordpress/googleanalytics.php', $installed_plugins) || array_key_exists('google-analytics-premium/googleanalytics-premium.php', $installed_plugins),
		'basename'  => 'google-analytics-for-wordpress/googleanalytics.php',
		'slug'      => 'google-analytics-for-wordpress',
		'settings'  => admin_url('admin.php?page=monsterinsights-settings'),
	);

	// WPForms.
	$plugins['wpforms-lite'] = array(
		'active'    => function_exists('wpforms'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-wpforms.png',
		'title'     => 'WPForms',
		'excerpt'   => __('The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 150+ form templates. Trusted by over 4 million websites as the best forms plugin', 'userfeedback'),
		'installed' => array_key_exists('wpforms-lite/wpforms.php', $installed_plugins),
		'basename'  => 'wpforms-lite/wpforms.php',
		'slug'      => 'wpforms-lite',
		'settings'  => admin_url('admin.php?page=wpforms-overview'),
	);

	// AIOSEO.
	$plugins['aioseo'] = array(
		'active'    => function_exists('aioseo'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-all-in-one-seo.png',
		'title'     => 'AIOSEO',
		'excerpt'   => __('The original WordPress SEO plugin and toolkit that improves your website’s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.', 'userfeedback'),
		'installed' => array_key_exists('all-in-one-seo-pack/all_in_one_seo_pack.php', $installed_plugins),
		'basename'  => (userfeedback_is_installed_aioseo_pro()) ? 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' : 'all-in-one-seo-pack/all_in_one_seo_pack.php',
		'slug'      => 'all-in-one-seo-pack',
		'settings'  => admin_url('admin.php?page=aioseo'),
	);

	// OptinMonster.
	$plugins['optinmonster'] = array(
		'active'    => class_exists('OMAPI'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-om.png',
		'title'     => 'OptinMonster',
		'excerpt'   => __('Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'userfeedback'),
		'installed' => array_key_exists('optinmonster/optin-monster-wp-api.php', $installed_plugins),
		'basename'  => 'optinmonster/optin-monster-wp-api.php',
		'slug'      => 'optinmonster',
		'settings'  => admin_url('admin.php?page=optin-monster-dashboard'),
	);

	// RafflePress
	$plugins['rafflepress'] = array(
		'active'    => function_exists('rafflepress_lite_activation'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-rafflepress.png',
		'title'     => 'RafflePress',
		'excerpt'   => __('Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'userfeedback'),
		'installed' => array_key_exists('rafflepress/rafflepress.php', $installed_plugins),
		'basename'  => 'rafflepress/rafflepress.php',
		'slug'      => 'rafflepress',
		'settings'  => admin_url('admin.php?page=rafflepress_lite'),
	);

	// SeedProd.
	$plugins['coming-soon'] = array(
		'active'    => defined('SEEDPROD_VERSION'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-seedprod.png',
		'title'     => 'SeedProd',
		'excerpt'   => __('The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'userfeedback'),
		'installed' => array_key_exists('coming-soon/coming-soon.php', $installed_plugins),
		'basename'  => 'coming-soon/coming-soon.php',
		'slug'      => 'coming-soon',
		'settings'  => admin_url('admin.php?page=seedprod_lite'),
	);

	// WP Mail Smtp.
	$plugins['wp-mail-smtp'] = array(
		'active'    => function_exists('wp_mail_smtp'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-smtp.png',
		'title'     => 'WP Mail SMTP',
		'excerpt'   => __('Improve your WordPress email deliverability and make sure that your website emails reach user’s inbox with the #1 SMTP plugin for WordPress. Over 2 million websites use it to fix WordPress email issues.', 'userfeedback'),
		'installed' => array_key_exists('wp-mail-smtp/wp_mail_smtp.php', $installed_plugins),
		'basename'  => 'wp-mail-smtp/wp_mail_smtp.php',
		'slug'      => 'wp-mail-smtp',
	);

	// EDD
	$plugins['easy-digital-downloads'] = array(
		'active'    => class_exists('Easy_Digital_Downloads'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-edd.svg',
		'title'     => 'Easy Digital Downloads',
		'excerpt'   => __('Easy Digital Downloads is a complete eCommerce solution for selling digital products on WordPress.', 'userfeedback'),
		'installed' => array_key_exists('easy-digital-downloads/easy-digital-downloads.php', $installed_plugins),
		'basename'  => 'easy-digital-downloads/easy-digital-downloads.php',
		'slug'      => 'easy-digital-downloads',
	);

	// Smash Balloon (Instagram)
	$plugins['smash-balloon-instagram'] = array(
		'active'    => defined('SBIVER'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-smash-balloon.png',
		'title'     => 'Smash Balloon Instagram Feeds',
		'excerpt'   => __('Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'userfeedback'),
		'installed' => array_key_exists('instagram-feed/instagram-feed.php', $installed_plugins),
		'basename'  => 'instagram-feed/instagram-feed.php',
		'slug'      => 'instagram-feed',
		'settings'  => admin_url('admin.php?page=sb-instagram-feed'),
	);

	// PushEngage
	$plugins['pushengage'] = array(
		'active'    => method_exists('Pushengage', 'init'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-pushengage.svg',
		'title'     => 'PushEngage',
		'excerpt'   => __('Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 9 billion notifications each month.', 'userfeedback'),
		'installed' => array_key_exists('pushengage/main.php', $installed_plugins),
		'basename'  => 'pushengage/main.php',
		'slug'      => 'pushengage',
	);
	
	// Uncanny Automator
	$plugins['uncanny-automator'] = array(
		'active'    => function_exists('automator_get_recipe_id'),
		'icon'      => plugin_dir_url(USERFEEDBACK_PLUGIN_FILE) . 'assets/img/plugins/plugin-uncanny-automator.png',
		'title'     => 'Uncanny Automator',
		'excerpt'   => __('Automate everything with the #1 no-code Automation tool for WordPress.', 'userfeedback'),
		'installed' => array_key_exists('uncanny-automator/uncanny-automator.php', $installed_plugins),
		'basename'  => 'uncanny-automator/uncanny-automator.php',
		'slug'      => 'uncanny-automator',
		'setup_complete'      => (bool) get_option('automator_reporting', false),
	);

	wp_send_json($plugins);
}
add_action('wp_ajax_userfeedback_get_plugins', 'userfeedback_get_plugins');
