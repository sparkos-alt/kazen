<?php

/**
 * Is This UserFeedback Pro?
 *
 * We use this function userfeedback_ to determine if the install is a pro version or a lite version install of UserFeedback.
 * If the install is a lite version we disable the install from admin functionality[1] for addons as WordPress.org requires us to,
 * we change the links for where to get support (wp.org forum for free; our site for pro), we use this determine what class to load as
 * the base class in addons (to avoid fatal errors) and we use this on the system info page to know what constants to display values for
 * as the lite and pro versions of our plugin have different constants (and names for those constants) you can declare and use.
 *
 * [1] Note: This is not "feature-locking" under GPL guidelines but rather something WordPress.org requires us to do to stay
 * in compliance with their rules. We wish we didn't have to do this, as in our oppinion this diminishes the user experience
 * of users installing our free and premium addons, and we'd love to turn this on for non-Pro installs, but we're not allowed to.
 * If WordPress.org ever changes their mind on this subject, we'd totally turn on that feature for Lite installs in a heartbeat.
 *
 * @todo  Are we allowed to turn on admin installing if the user has to manually declare a PHP constant (and thus would not be on
 * either by default or via any sort of user interface)? If so, we could add a constant for forcing Pro version so that users can see
 * for themselves that we're not feature locking anything inside the plugin + it would make it easier for our team to test stuff (both via
 * Travis-CI but also when installing addons to test with the Lite version). Also this would allow for a better user experience for users
 * who want that feature.
 *
 * @since 1.0.0
 * @access public
 *
 * @return bool True if pro version.
 */
function userfeedback_is_pro_version()
{
	return class_exists('UserFeedback');
}

function userfeedback_is_licensed()
{
	return UserFeedback()->license->is_site_licensed() || UserFeedback()->license->is_network_licensed();
}

function userfeedback_get_license_type()
{
	$license_type = UserFeedback()->license->get_site_license_type();

	if (empty($license_type)) {
		return UserFeedback()->license->get_network_license_type();
	}

	return $license_type;
}

/**
 * Get the user roles of this WordPress blog
 *
 * @return array
 */
function userfeedback_get_roles()
{
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$roles     = array();

	/**
	 * Filter: 'editable_roles' - Allows filtering of the roles shown within the plugin (and elsewhere in WP as it's a WP filter)
	 *
	 * @api array $all_roles
	 */
	$editable_roles = apply_filters('editable_roles', $all_roles);

	foreach ($editable_roles as $id => $name) {
		$roles[$id] = translate_user_role($name['name']);
	}

	return $roles;
}

/**
 * Get the user roles which can manage options. Used to prevent these roles from getting unselected in the settings.
 *
 * @return array
 */
function userfeedback_get_manage_options_roles()
{
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	$roles     = array();

	/**
	 * Filter: 'editable_roles' - Allows filtering of the roles shown within the plugin (and elsewhere in WP as it's a WP filter)
	 *
	 * @api array $all_roles
	 */
	$editable_roles = apply_filters('editable_roles', $all_roles);

	foreach ($editable_roles as $id => $role) {
		if (isset($role['capabilities']['manage_options']) && $role['capabilities']['manage_options']) {
			$roles[$id] = translate_user_role($role['name']);
		}
	}

	return $roles;
}

function userfeedback_is_dev_url($url = '')
{
	$is_local_url = false;
	// Trim it up
	$url = strtolower(trim($url));
	// Need to get the host...so let's add the scheme so we can use parse_url
	if (false === strpos($url, 'http://') && false === strpos($url, 'https://')) {
		$url = 'http://' . $url;
	}
	$url_parts = parse_url($url);
	$host      = !empty($url_parts['host']) ? $url_parts['host'] : false;
	if (!empty($url) && !empty($host)) {
		if (false !== ip2long($host)) {
			if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
				$is_local_url = true;
			}
		} elseif ('localhost' === $host) {
			$is_local_url = true;
		}

		$tlds_to_check = array('.local', ':8888', ':8080', ':8081', '.invalid', '.example', '.test');
		foreach ($tlds_to_check as $tld) {
			if (false !== strpos($host, $tld)) {
				$is_local_url = true;
				break;
			}
		}
		if (substr_count($host, '.') > 1) {
			$subdomains_to_check = array('dev.', '*.staging.', 'beta.', 'test.');
			foreach ($subdomains_to_check as $subdomain) {
				$subdomain = str_replace('.', '(.)', $subdomain);
				$subdomain = str_replace(array('*', '(.)'), '(.*)', $subdomain);
				if (preg_match('/^(' . $subdomain . ')/', $host)) {
					$is_local_url = true;
					break;
				}
			}
		}
	}
	return $is_local_url;
}

function userfeedback_get_licensing_url()
{
	return apply_filters('userfeedback_get_licensing_url', 'https://www.userfeedback.com');
}

function userfeedback_get_asset_version()
{
	if (userfeedback_is_debug_mode() || (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG)) {
		return time();
	} else {
		return USERFEEDBACK_VERSION;
	}
}

function userfeedback_is_debug_mode()
{
	$debug_mode = false;
	if (defined('USERFEEDBACK_DEBUG_MODE') && USERFEEDBACK_DEBUG_MODE) {
		$debug_mode = true;
	}

	return apply_filters('userfeedback_is_debug_mode', $debug_mode);
}

function userfeedback_get_inline_menu_icon()
{
	$scheme          = get_user_option('admin_color', get_current_user_id());
	$use_dark_scheme = $scheme === 'light';
	if ($use_dark_scheme) {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAFQUlEQVRYha2Yb2hXZRTHP+c3nc6pm07NF0KWWUtSo0wqzBdiZRItTKMaEZXSi0zRNAsqTBKKSFOa0B8Jigqz2lSwLMtqRURgRuCCLLNmselyZups2+/04pzbnt3de3eTDlzufc5znvN8n+ec55zzXFFV8pKITANOqmpTP3JTgIKq7sutPCJVzfUABeAb4DSwMENuKdABNObV3Wv8fwB0C6DAUX8/67sQ9Q8ANsVk5v5vgIDKWHsvcAgYCWzzCbc6kFJgh/PqgVHAb8DnWTpzA3LzHARmeXuqT/Zo0L/eeZuAV/x7fbRrwJPOu9Dbc4EDgJwNoMmurAt4Bljt7cmBjACvOl+BzTEdVzj/EWAj0O3tC84G0AIf3BRMeDz0GZcbBvzqKy+L9Q30A6AxXTdmARqQcPAAyv29CBjjO1RU1SKAiIwGFgLX+MrbgBnAh5ECVe0UkUMO6nHgFLA70J1McacD5gHbfTXzg77qwBeOBysPn830PnnVwXety7wL1AAV/ZoM+MIHdQCfAdfF+s8H/koBEz0rU9xgLtAInHG5j/KYrNWf8ap6OmFD7w+2/Cugwd/NmOkqgbIUS+wEdorIEOAwFqv6UBKgihQwANNc0b2quh1ARIZi/nUqZUycOrDDcCSps5AAaJBPkkStwNVAs4i8JiLHgBPASRFpFZEGEZktIpIBqBIoIWWH4nZegtl3fIofjAKeoyemfAe8hZnu64D/NjAsRcdEl1mcx6lvc+HLU6L3O97/JXBlgszF9KSVvXhswkxUC6wLdKzIA2iWC1+fMNlK72sASlMjrQHf4LIvAw8B7fScwmNAZ7DDs7MARSmjNsYf7oqak0wBjAXuBlb5Lo9wE0Yg6rHAOdjlR2KB9Qc384o0QOe4giUx/u3OX5oA5gEsCoexqBnYAxTTfMXHlvuOF4F5SYBKHPGaGH+jTzQxxefSnnVpYAIdg9x0PwEDkwSOAHUx3hafoDzGP5AB5gQ56h/XU+NjauJxCCxRjo7xOvw9ImKISBUwIWF8RLtVtT2jP6SdWBKe1QuQiCwDLsKcNKSoqJ8e8BJTREAHc4JBVTuBn4Gx/wISkflYndyNOXdI2/29OOAd7mfSIXkBOZUDxTACt2A78SLQnmDnBszOiwLeraT70Ld5/Mf1jPMxqyLGWqxcnYoFMqVvBTgOK9y7gOVAifMfdF4SqJk5Aa3FLFMNduxagQbvvJOUfIb51/f0lKSrsROyHCtlIyDtrrMJqOoHzAysRvrA28wmSBfAtd7uk6u8vwwr/JOqxm4sl01wvZ3AfhJyo+taAPyJhYi/gekCPIXdNitV9YyIXIIFqptVdVsf13MSkVJgJlZF4rvSqKq/BzJzgNexcPEp8LFPXAHcAFzqoKcAddjR5z2Cay/m4Arcl9cp+zFJFfA0dslMOwB1wD1AewGrTw4Ei2/zVcSP/lmRqrap6irs8gAwid7xDOAuzNwlgmXxF1T14ahXRPZjtU1k3+g5Tk8pkUUFzCwVWC003N/DgGVYIXheIF/EfmQcFczDW4DnsVtBCxbUtmIOPAAzY6MPLgMG+/dlDrIADHWlYL4QpZuZWLjYgp3SOb7QMbFFFLF6LDNB7sGcri7FP7qwWmcX9t8oSWaDA6zCqomXUuZ6U1UpYDXxH5jfgKWET/y7zXfolIgkJeJMEpES/xwMXKWq3aq6CLu9PAH8Eog/Fn2UYnlkDWa2c719E3Y/f8NX0AL8GHuianAXtuXx/lZ6brR9/npgcWgHcEfEkyg6ZqyyBrt1ptE+X9SkDJl6VX0/cyKnfwBb6gwNaZ8ExgAAAABJRU5ErkJggg';
	} else {
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAYAAADhAJiYAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAA3XAAAN1wFCKJt4AAAAB3RJTUUH4AoEBjcfBsDvpwAABQBJREFUWMO1mGmollUQgJ9z79Vc01LLH0GLWRqlUhYV5o+LbRIVbVQSUSn9qJTKsqDCoqCINKUbtBEUFbbeDGyz1SIiaCHIINu18KZ1bbkuV+/Tj+arw8v7fvdVcuDjvGdmzsycM3Nm5nywE6BOVSfW4JukTmF3gtqifqJuVmc34ZunblFX7W6DzvYf2BDjPWpLRm9T7y/wzPw/DRhZmH+sfq/urb4YCp8JQwaqLwXuBXW0+pP6XjOZO+ueb9X2mE8OZTdl9MWBu199NL4XN05NvT1wh8R8prpGTbti0BEhbLt6t7ow5kdkPEl9zP/gkYKMowN/o7pU3RHzg3fFoHNj8epM4aY8ZoJvuPpj7HxwgTYgLoAFWac1091WgR8a4xxgH2Ah0JdS6gtlY4DZwAnADmAjMA14vSEgpdSrfg9sBm4BeoCVmex6gayepS6P3ZyT0SZksbDJcnikcPMmZN+zgud59Qx1RB2D3o9FW9R31ZMK9IPUP20O11XInqmuUrcG3xt1XNYVvwNSSptL+K/IjvxDoDPGteG6kcDgMkUppRXACnUIsA7YUNegERXGAEwNQZellJbHzodFfPXUjIwtwHDglzJiS4lBe4SSMugCjgfWqo+rvwF/AH+pXWqnOqOfXDMSaK06oaKf54Z/D6igj1bvzXLK5+rTYchHGf5ZdXiFjPHBc2Udg84P5qMqsvdzQf9APbaEZ2JWVj5u5KbIV7PURZmM+XUMag/mk0to1wWtUx3YT9lZErwPq9er3dkt/E3tzU54Rp2SMauA3zMErS1zhTpWvURdEKe8V7jQrOBOUwcF/97qbPWrcPP8KoP2DQFzC/gLAj+vZM1Vak8hF61V31L7msWKOjROvE89q4yhNSy+rYBfGorGV8RcFSyqESZ7hOu+UQeUMfyidhRwy0LB0AJ+TRNj/qjb/0QpUT2jpYS+ERhTkswA9sqEjALGNdGzMqXUXTNZrogi3F5sJ64GDgXGFhasjvGYDDe4HyXf1i3qKaVe4DtgbF6ZzwHuiZq0b2HN8hjzAF3Xj9IhO9mGDQX68gy8PpqoB9XuEj93hp/nZLjzmsTQZzvR9uwXaxY0EHdEuzo5EpklHeB+0bhvV69RWwN/beDKYHpNg+6I2z2hce261M4gXlRVz9RD1S+zlnRh3JBropVtQHfIXB3B38yYadEjvdZAzMjLhXpizI+tEDA4Gv+yrnFH1LJxIbdX/aKsNma9+++RIrapxyT1TmAeMDKltFU9HPgcODOl9GKTnQ0EpgMHBaobWJVS+jnjOQV4ItLFO8CbwDZgBHAqMAXoBSYBHcBm1JfzZ28EuOrl/9ODc5R6Vzwyq6BDvVTtbgHGA2sKiXFbydXfJUgpbUwpLQAateqwQj4DuDjSTWuKru+BlNIN2a6+ACYCv0dH2PhtCtfYjx0t4ZYR0a7uGeNw4GpgLnBgxt8HfAJsSOpWYD1wH7AqvocAz0Q2bgNGB62RoQfF95FhZAswLIQSZaBRbqYDPwHLogqcEhvdp7CJPqC9vwL5VtyUjor42B69zqvqXxU8S+IFOyq6iYcqdD3VONqngV8jbhol4e0sntqAnuIzumZAt8bnIOC4lNKOlNKceL3cCvyQsd/87/WNRuk29T51/5ifHu/zJ2MH69WvCz+zE+oroXdlL9pUkYdeUi/89xLU6VWAZn88fQoMjNtTBS+klF6pc6p/A2ye4OCYzm1lAAAAAElFTkSuQmCC';
	}
}

/**
 * Returns a HEX color to highlight menu items based on the admin color scheme.
 */
function userfeedback_menu_highlight_color()
{

	$color_scheme = get_user_option('admin_color');
	$color        = '#1da867';
	if ('light' === $color_scheme || 'blue' === $color_scheme) {
		$color = '#5f3ea7';
	}

	return $color;
}

/**
 * Helper function to check if the current user can install a plugin.
 *
 * @return bool
 */
function userfeedback_can_install_plugins()
{

	if (!current_user_can('install_plugins')) {
		return false;
	}

	// Determine whether file modifications are allowed.
	if (function_exists('wp_is_file_mod_allowed') && !wp_is_file_mod_allowed('userfeedback_can_install')) {
		return false;
	}

	return true;
}

/**
 * Check if current date is between given dates. Date format: Y-m-d.
 *
 * @since 7.13.2
 *
 * @param string $start_date Start Date. Eg: 2021-01-01.
 * @param string $end_date   End Date. Eg: 2021-01-14.
 *
 * @return bool
 */
function userfeedback_date_is_between($start_date, $end_date)
{

	$current_date = current_time('Y-m-d');

	$start_date = date('Y-m-d', strtotime($start_date));
	$end_date   = date('Y-m-d', strtotime($end_date));

	if (($current_date >= $start_date) && ($current_date <= $end_date)) {
		return true;
	}

	return false;
}

function userfeedback_screen_is_userfeedback()
{
	// Get current screen.
	$screen = get_current_screen();
	return !empty($screen->id) && strpos($screen->id, 'userfeedback') !== false;
}

function userfeedback_screen_is_surveys()
{
	$screen = get_current_screen();
	return strpos($screen->id, 'userfeedback_surveys') !== false;
}

function userfeedback_screen_is_results()
{
	$screen = get_current_screen();
	return strpos($screen->id, 'userfeedback_results') !== false;
}

function userfeedback_screen_is_settings()
{
	$screen = get_current_screen();
	return strpos($screen->id, 'userfeedback_settings') !== false;
}

function userfeedback_screen_is_smtp()
{
	$screen = get_current_screen();
	return strpos($screen->id, 'userfeedback_smtp') !== false;
}

function userfeedback_screen_is_wp_dashboard()
{
	$screen = get_current_screen();
	return $screen->id === 'dashboard';
}

function userfeedback_screen_is_exports()
{
	$screen = get_current_screen();
	return $screen->id === 'admin_page_userfeedback_exports';
}

function userfeedback_screen_is_install()
{
	$screen = get_current_screen();
	return $screen->id === 'admin_page_userfeedback_plugin_install';
}

function userfeedback_is_plugin_installed($slug)
{
	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	$all_plugins = get_plugins();

	if (!empty($all_plugins[$slug])) {
		return true;
	} else {
		return false;
	}
}

function userfeedback_get_notice_hide_opt_prefix()
{
	return 'userfeedback_vue_notice_hidden_';
}

function userfeedback_get_wp_notice_hide_opt_prefix()
{
	return 'userfeedback_vue_wp_notice_hidden_';
}

/**
 * Check WP version and include the compatible upgrader skin.
 *
 * @param bool $custom_upgrader If true it will include our custom upgrader, otherwise it will use the default WP one.
 */
function userfeedback_require_upgrader($custom_upgrader = true)
{
	global $wp_version;

	$base = UserFeedback();

	if (!$custom_upgrader) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	} else {
		require_once plugin_dir_path($base->file) . 'includes/admin/licensing/plugin-upgrader.php';
	}

	// WP 5.3 changes the upgrader skin.
	if (version_compare($wp_version, '5.3', '<')) {
		require_once plugin_dir_path($base->file) . '/includes/admin/licensing/skin-legacy.php';
	} else {
		require_once plugin_dir_path($base->file) . '/includes/admin/licensing/skin.php';
	}
}

function userfeedback_get_screen_url($page, $path = null, $scroll_to = null)
{

	$url = add_query_arg(
		array(
			'page'                   => $page,
			'userfeedback-scroll'    => $scroll_to,
			'userfeedback-highlight' => $scroll_to,
		),
		admin_url('admin.php')
	);

	if (!empty($path)) {
		$url .= '#/' . $path;
	}

	return $url;
}

/** Decode special characters, both alpha- (<) and numeric-based (').
 *
 * @since 1.0.0
 *
 * @param string $string Raw string to decode.
 *
 * @return string
 */
function userfeedback_decode_string($string)
{

	if (!is_string($string)) {
		return $string;
	}

	return wp_kses_decode_entities(html_entity_decode($string, ENT_QUOTES));
}

add_filter('userfeedback_email_message', 'userfeedback_decode_string');

/**
 * Sanitize a string, that can be a multiline.
 * If WP core `sanitize_textarea_field()` exists (after 4.7.0) - use it.
 * Otherwise - split onto separate lines, sanitize each one, merge again.
 *
 * @since 1.0.0
 *
 * @param string $string
 *
 * @return string If empty var is passed, or not a string - return unmodified. Otherwise - sanitize.
 */
function userfeedback_sanitize_textarea_field($string)
{

	if (empty($string) || !is_string($string)) {
		return $string;
	}

	if (function_exists('sanitize_textarea_field')) {
		$string = sanitize_textarea_field($string);
	} else {
		$string = implode("\n", array_map('sanitize_text_field', explode("\n", $string)));
	}

	return $string;
}

function userfeedback_get_frontend_widget_settings()
{
	$userfeedback_settings = userfeedback_get_options();

	$use_custom_logo = userfeedback_is_pro_version() && userfeedback_is_licensed();
	$custom_logo     = !empty($userfeedback_settings['widget_custom_logo']) ? $userfeedback_settings['widget_custom_logo'] : '';
	
	return array(
		'start_minimized' => $userfeedback_settings['widget_start_minimized'],
		'show_logo'       => userfeedback_show_logo($userfeedback_settings),
		'custom_logo'     => ($use_custom_logo && !empty($custom_logo)) ? $custom_logo : '',
		'position'        => $userfeedback_settings['widget_position'],
		'widget_toggle_icon'     => isset($userfeedback_settings['widget_toggle_icon']) ? $userfeedback_settings['widget_toggle_icon'] : 'field-chevron-down',
		'widget_toggle_color'     => isset($userfeedback_settings['widget_toggle_color']) ? $userfeedback_settings['widget_toggle_color'] : '#23282D',
		'widget_toggle_text'     => isset($userfeedback_settings['widget_toggle_text']) ? $userfeedback_settings['widget_toggle_text'] : '',
		'widget_font'     => isset($userfeedback_settings['widget_font']) ? $userfeedback_settings['widget_font'] : false,
		'widget_color'     => isset($userfeedback_settings['widget_color']) ? $userfeedback_settings['widget_color'] : '#ffffff',
		'text_color'     => isset($userfeedback_settings['widget_text_color']) ? $userfeedback_settings['widget_text_color'] : '#23282D',
		'button_color'     => isset($userfeedback_settings['widget_button_color']) ? $userfeedback_settings['widget_button_color'] : '#2D87F1',
		'default_widget_color'     => '#ffffff',
		'default_text_color'     => '#23282D',
		'default_button_color'     => '#2D87F1',

	);
}

function userfeedback_show_logo($userfeedback_settings)
{
	if (isset($userfeedback_settings['logo_type'])) {
		if ($userfeedback_settings['logo_type'] == 'none') {
			return false;
		}
		if ($userfeedback_settings['logo_type'] == 'userfeedback') {
			return true;
		}
		if ($userfeedback_settings['logo_type'] == 'custom') {
			return true;
		}
	}

	if(isset($userfeedback_settings['widget_show_logo'])){
		if($userfeedback_settings['widget_show_logo']){
			return true;
		}
	}

	return false;
}

/**
 * Check if AIOSEO Pro version is installed or not.
 *
 * @since 1.0.0
 *
 * @return bool
 */
function userfeedback_is_installed_aioseo_pro()
{
	$installed_plugins = get_plugins();

	if (array_key_exists('all-in-one-seo-pack-pro/all_in_one_seo_pack.php', $installed_plugins)) {
		return true;
	}

	return false;
}

function userfeedback_get_shareasale_id()
{
	// Check if there's a constant.
	$shareasale_id = '';
	if (defined('USERFEEDBACK_SHAREASALE_ID')) {
		$shareasale_id = USERFEEDBACK_SHAREASALE_ID;
	}

	// If there's no constant, check if there's an option.
	if (empty($shareasale_id)) {
		$shareasale_id = get_option('userfeedback_shareasale_id', '');
	}

	// Whether we have an ID or not, filter the ID.
	$shareasale_id = apply_filters('userfeedback_shareasale_id', $shareasale_id);

	// Ensure it's a number
	$shareasale_id = absint($shareasale_id);

	return $shareasale_id;
}

function userfeedback_is_tracking_allowed()
{
	return (bool) userfeedback_get_option('allow_usage_tracking', false);
}

if (!function_exists('wp_get_jed_locale_data')) {
	/**
	 * Returns Jed-formatted localization data. Added for backwards-compatibility.
	 *
	 * @param  string $domain Translation domain.
	 *
	 * @return array
	 */
	function wp_get_jed_locale_data($domain)
	{
		$translations = get_translations_for_domain($domain);

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale(),
			),
		);

		if (!empty($translations->headers['Plural-Forms'])) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ($translations->entries as $msgid => $entry) {
			$locale[$msgid] = $entry->translations;
		}

		return $locale;
	}
}



if (!function_exists('userfeedback_get_type_of_page')) {
	function userfeedback_get_type_of_page()
	{
		global $wp_query;

		if (!isset($wp_query)) {
			return '';
		}
		if (is_front_page() || is_home()) {
			return 'is_front_page';
		}
		if (is_singular()) {
			return 'is_single';
		}
		if (is_archive()) {
			return 'is_archive';
		}
		if (is_search()) {
			return 'is_search';
		}
		if (is_404()) {
			return 'is_404';
		}
		if (is_author()) {
			return 'is_author';
		}

		return '';
	}
}

if (!function_exists('userfeedback_get_taxonomy')) {
	function userfeedback_get_taxonomy()
	{
		global $wp_query;
		if (is_null($wp_query)) {
			return '';
		}
		$queried_object = get_queried_object();

		return isset($queried_object->taxonomy) ? $queried_object->taxonomy : false;
	}
}

if (!function_exists('userfeedback_get_term')) {
	function userfeedback_get_term()
	{
		$object = get_queried_object();
		return ( ! empty( $object->term_id ) ) ? $object->term_id : false;
	}
}

if (!function_exists('userfeedback_get_current_url')) {
	function userfeedback_get_current_url()
	{
		global $wp;
		return add_query_arg($wp->query_vars, home_url($wp->request));
	}
}
