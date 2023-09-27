<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wpfp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}


define('AUTH_KEY',         'oQxcqu358sOayUl1WKAIm4KyvLYUZyk7p2G0MJcM0NQbI2G0/IwpbCedj8JS4Mnd82qxmuGFKnvofjGQIYE07A==');
define('SECURE_AUTH_KEY',  'UsiCpt5zMpBMc65Ga0UuNINcjLW1+ZngTnJRPWKExuDkCT95i+CyE3D+5jn3SeLNwrTKvp2bcM9+w5D6/66L8w==');
define('LOGGED_IN_KEY',    'a7YyoqGZUJVXxyh7AuPvQf5Xs9b6WZ8IfzOaG5OOjOTPdXZRgtHYzwwiOMUGove1Ukeh0A1FbUUxdfEIVg9dJg==');
define('NONCE_KEY',        '/ssoiInoLkVKOgfLSzNcOQt9snN+lGBV3d0tfK87puVSmtnMuXMhui6zQ6DuDm9EfG5Ac12JQ592lZdKcSOIKQ==');
define('AUTH_SALT',        'fgdvsJf0KC0A7vjDPBzMWgEfg3VC+wHlSGZcdgqysbh8InqflqPTkHON/9g0Hk+vH7gljE1R53GRvMVloP0iog==');
define('SECURE_AUTH_SALT', 'F9G1UsLK2zkDV4+/xnhdBwSJAopEvgBtrW4GI/Ia2AVeVRYhCCZR+NLSfbQJSwcXMqdTAtGfPQ6fz3IuKfm4Fw==');
define('LOGGED_IN_SALT',   '9QnrLSeXEFeeS2zyrUDJ3EFvFXPgFIkXuYuiblkHQaBcsE15rabJ+7IqzxSzY4WgJN9xCeEkSLwKvAj2EavV1Q==');
define('NONCE_SALT',       'sgKv73WiVo0dlBqxQNJrMAZaG8RCM93sBseMyviK+59Iz3XqO2rp5lag5N2Aj5c/ojOqlKNS+rfL5/4JyKqvWw==');
define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
